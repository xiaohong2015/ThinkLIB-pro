<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace think\admin\command;

use think\admin\Command;
use think\Collection;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 异步任务管理指令
 * Class Queue
 * @package think\admin\command
 */
class Queue extends Command
{

    /**
     * 任务编号
     * @var string
     */
    protected $code;

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'SystemQueue';

    /**
     * 配置指令参数
     */
    public function configure()
    {
        $this->setName('xadmin:queue');
        $this->addArgument('action', Argument::OPTIONAL, 'stop|start|status|listen|dorun', 'listen');
        $this->addArgument('code', Argument::OPTIONAL, 'Taskcode');
        $this->addArgument('spts', Argument::OPTIONAL, 'Separator');
        $this->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the queue listen in daemon mode');
        $this->setDescription('Asynchronous Command Queue for ThinkAdmin');
    }

    /**
     * 执行指令内容
     * @param Input $input
     * @param Output $output
     */
    public function execute(Input $input, Output $output)
    {
        $action = $this->input->hasOption('daemon') ? 'start' : $input->getArgument('action');
        if (method_exists($this, $method = "{$action}Action")) $this->$method();
        $this->output->error("Wrong operation, currently allow stop|start|status|listen|dorun");
    }

    /**
     * 停止任务
     */
    protected function stopAction()
    {
        $keyword = $this->process->think('xadmin:queue');
        if (count($result = $this->process->query($keyword)) < 1) {
            $this->output->warning("There is no task process to finish");
        } else foreach ($result as $item) {
            $this->process->close($item['pid']);
            $this->output->info("Sending end process {$item['pid']} signal succeeded");
        }
    }

    /**
     * 启动任务
     */
    protected function startAction()
    {
        $this->app->db->name($this->table)->count();
        $command = $this->process->think("xadmin:queue listen");
        if (count($result = $this->process->query($command)) > 0) {
            $this->output->info("Listening main process {$result['0']['pid']} has started");
        } else {
            [$this->process->create($command), sleep(1)];
            if (count($result = $this->process->query($command)) > 0) {
                $this->output->info("Listening main process {$result['0']['pid']} started successfully");
            } else {
                $this->output->error('Failed to create listening main process');
            }
        }
    }

    /**
     * 查询任务
     */
    protected function statusAction()
    {
        $command = $this->process->think('xadmin:queue listen');
        if (count($result = $this->process->query($command)) > 0) {
            $this->output->info("Listening for main process {$result[0]['pid']} running");
        } else {
            $this->output->warning("The Listening main process is not running");
        }
    }

    /**
     * 监听任务
     */
    protected function listenAction()
    {
        set_time_limit(0);
        $this->app->db->name($this->table)->count();
        if ($this->process->iswin()) {
            $this->setProcessTitle("ThinkAdmin {$this->process->version()} Queue Listen");
        }
        $this->output->writeln('You can exit with <info>`CTRL-C`</info>');
        $this->output->writeln('============= LISTENING =============');
        while (true) {
            $where = [['status', '=', '1'], ['exec_time', '<=', time()]];
            $this->app->db->name($this->table)->where($where)->order('exec_time asc')->chunk(100, function (Collection $result) {
                foreach ($result->toArray() as $vo) try {
                    $command = $this->process->think("xadmin:queue dorun {$vo['code']} -");
                    if (count($this->process->query($command)) > 0) {
                        $this->output->writeln("Already in progress -> [{$vo['code']}] {$vo['title']}");
                    } else {
                        $this->process->create($command);
                        $this->output->writeln("Created new process -> [{$vo['code']}] {$vo['title']}");
                    }
                } catch (\Exception $exception) {
                    $this->app->db->name($this->table)->where(['code' => $vo['code']])->update([
                        'status' => '4', 'outer_time' => time(), 'exec_desc' => $exception->getMessage(),
                    ]);
                    $this->output->error("Execution failed -> [{$vo['code']}] {$vo['title']}，{$exception->getMessage()}");
                }
            });
            usleep(500000);
        }
    }

    /**
     * 执行任务
     * @throws \think\db\exception\DbException
     */
    protected function dorunAction()
    {
        set_time_limit(0);
        $this->code = trim($this->input->getArgument('code'));
        if (empty($this->code)) {
            $this->output->error('Task number needs to be specified for task execution');
        } else try {
            $this->queue->initialize($this->code);
            if (empty($this->queue->record) || intval($this->queue->record['status']) !== 1) {
                // 这里不做任何处理（该任务可能在其它地方已经在执行）
                $this->output->warning($message = "The or status of task {$this->code} is abnormal");
            } else {
                // 锁定任务状态，防止任务再次被执行
                $this->app->db->name($this->table)->strict(false)->where(['code' => $this->code])->update([
                    'enter_time' => microtime(true), 'attempts' => $this->app->db->raw('attempts+1'),
                    'outer_time' => '0', 'exec_pid' => getmypid(), 'exec_desc' => '', 'status' => '2',
                ]);
                $this->queue->progress(2, '>>> 任务处理开始 <<<', 0);
                // 设置进程标题
                if ($this->process->iswin()) {
                    $this->setProcessTitle("ThinkAdmin {$this->process->version()} Queue - {$this->queue->title}");
                }
                // 执行任务内容
                defined('WorkQueueCall') or define('WorkQueueCall', true);
                defined('WorkQueueCode') or define('WorkQueueCode', $this->code);
                if (class_exists($command = $this->queue->record['command'])) {
                    // 自定义任务，支持返回消息（支持异常结束，异常码可选择 3|4 设置任务状态）
                    $class = $this->app->make($command, [], true);
                    if ($class instanceof \think\admin\Queue) {
                        $this->update(3, $class->initialize($this->queue)->execute($this->queue->data));
                    } elseif ($class instanceof \think\admin\service\QueueService) {
                        $this->update(3, $class->initialize($this->queue->code)->execute($this->queue->data));
                    } else {
                        throw new \think\admin\Exception("自定义 {$command} 未继承 Queue 或 QueueService");
                    }
                } else {
                    // 自定义指令，不支持返回消息（支持异常结束，异常码可选择 3|4 设置任务状态）
                    $attr = explode(' ', trim(preg_replace('|\s+|', ' ', $this->queue->record['command'])));
                    $this->update(3, $this->app->console->call(array_shift($attr), $attr)->fetch(), false);
                }
            }
        } catch (\Exception|\Error $exception) {
            $code = $exception->getCode();
            if (intval($code) !== 3) $code = 4;
            $this->update($code, $exception->getMessage());
        }
    }

    /**
     * 修改当前任务状态
     * @param integer $status 任务状态
     * @param string $message 消息内容
     * @param boolean $issplit 是否分隔
     * @throws \think\db\exception\DbException
     */
    protected function update($status, $message, $issplit = true)
    {
        // 更新当前任务
        $info = trim(is_string($message) ? $message : '');
        $desc = $issplit ? explode("\n", $info) : [$message];
        $this->app->db->name($this->table)->strict(false)->where(['code' => $this->code])->update([
            'status' => $status, 'outer_time' => microtime(true), 'exec_pid' => getmypid(), 'exec_desc' => $desc[0],
        ]);
        $this->output->writeln(is_string($message) ? $message : '');
        // 任务进度标记
        if (!empty($desc[0])) {
            $this->queue->progress($status, ">>> {$desc[0]} <<<");
        }
        if ($status == 3) {
            $this->queue->progress($status, '>>> 任务处理完成 <<<', 100);
        } elseif ($status == 4) {
            $this->queue->progress($status, '>>> 任务处理失败 <<<');
        }
        // 注册循环任务
        if (isset($this->queue->record['loops_time']) && $this->queue->record['loops_time'] > 0) {
            try {
                $this->queue->initialize($this->code)->reset($this->queue->record['loops_time']);
            } catch (\Exception|\Error $exception) {
                $this->app->log->error("Queue {$this->queue->record['code']} Loops Failed. {$exception->getMessage()}");
            }
        }
    }
}