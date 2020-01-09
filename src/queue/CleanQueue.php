<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkLibrary
// | github 代码仓库：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace think\admin\queue;

use think\admin\service\QueueService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * 清理历史任务记录
 * Class CleanQueue
 * @package think\admin\queue
 */
class CleanQueue extends Command
{
    /**
     * 截止时间
     * @var integer
     */
    protected $time;

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'SystemQueue';

    /**
     * 配置指定信息
     */
    protected function configure()
    {
        $this->setName('xtask:clean')->setDescription('Clean up historical task records');
        $this->addArgument('time', Argument::OPTIONAL, 'BeforeTime', 7 * 24 * 3600);
    }

    /**
     * 清理历史任务
     * @param Input $input
     * @param Output $output
     * @throws \think\db\exception\DbException
     * @throws \think\Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $this->time = $input->getArgument('time');
        if (empty($this->time) || !is_numeric($this->time) || $this->time <= 0) {
            $this->output->error('Wrong parameter, the deadline needs to be an integer');
        } else {
            $map = [['exec_time', '<', time() - $this->time]];
            $count1 = $this->app->db->name($this->table)->where($map)->delete();
            $this->output->info("Successfully cleaned up {$count1} history task records");
            // 重置超1小时无响应的记录
            $map = [['exec_time', '<', time() - 3600], ['status', '=', '2']];
            $count2 = $this->app->db->name($this->table)->where($map)->update(['status' => '4', 'exec_desc' => '执行等待超过1小时无响应']);
            $this->output->info("Failed {$count2} records without response after waiting for more than 1 hour");
            // 返回消息到任务状态描述
            if (defined('WorkQueueCall')) {
                throw new \think\Exception("清理 {$count1} 条任务记录，标志 {$count2} 条超1小时无响应的任务", 3);
            }
        }
    }
}