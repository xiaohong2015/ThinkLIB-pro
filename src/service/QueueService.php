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

namespace think\admin\service;

use think\admin\extend\CodeExtend;
use think\admin\Service;

/**
 * 任务基础服务
 * Class QueueService
 * @package think\admin\service
 */
class QueueService extends Service
{

    /**
     * 当前任务编号
     * @var integer
     */
    protected $code = 0;

    /**
     * 当前任务标题
     * @var string
     */
    protected $title = '';

    /**
     * 当前任务参数
     * @var array
     */
    protected $data = [];

    /**
     * 当前任务数据
     * @var array
     */
    protected $queue = [];

    /**
     * 数据初始化
     * @param integer $code
     * @return static
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function initialize($code = 0): Service
    {
        if (!empty($code)) {
            $this->code = $code;
            $this->queue = $this->app->db->name('SystemQueue')->where(['code' => $this->code])->find();
            if (empty($this->queue)) throw new \think\Exception("Queue {$code} Not found.");
            $this->code = $this->queue['code'];
            $this->title = $this->queue['title'];
            $this->data = json_decode($this->queue['exec_data'], true) ?: [];
        }
        return $this;
    }

    /**
     * 判断是否WIN环境
     * @return boolean
     */
    protected function iswin()
    {
        return ProcessService::instance()->iswin();
    }

    /**
     * 重发异步任务
     * @param integer $wait 等待时间
     * @return $this
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function reset($wait = 0)
    {
        if (empty($this->queue)) throw new \think\Exception('Queue data cannot be empty!');
        $this->app->db->name('SystemQueue')->where(['code' => $this->code])->failException(true)->update([
            'exec_pid' => '0', 'exec_time' => time() + $wait, 'attempts' => $this->queue['attempts'] + 1, 'status' => '1',
        ]);
        return $this->initialize($this->code);
    }

    /**
     * 添加清理7天前的记录及超时任务
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addCleanQueue()
    {
        $this->register('清理7天前的记录及执行超时任务', "xtask:clean", 0, [], 0, 3600);
    }

    /**
     * 注册异步处理任务
     * @param string $title 任务名称
     * @param string $command 执行内容
     * @param integer $later 延时时间
     * @param array $data 任务附加数据
     * @param integer $rscript 任务类型(0单例,1多例)
     * @param integer $loops 循环等待时间
     * @return $this
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function register($title, $command, $later = 0, $data = [], $rscript = 1, $loops = 0)
    {
        $map = [['title', '=', $title], ['status', 'in', ['1', '2']]];
        if (empty($rscript) && $this->app->db->name('SystemQueue')->where($map)->count() > 0) {
            throw new \think\Exception(lang('think_library_queue_exist'));
        }
        $this->app->db->name('SystemQueue')->strict(false)->failException(true)->insert([
            'code'       => $this->code = 'QE' . CodeExtend::uniqidDate(16),
            'title'      => $title,
            'command'    => $command,
            'attempts'   => '0',
            'rscript'    => intval(boolval($rscript)),
            'exec_data'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            'exec_time'  => $later > 0 ? time() + $later : time(),
            'enter_time' => '0',
            'outer_time' => '0',
            'loops_time' => $loops,
        ]);
        return $this->initialize($this->code);
    }

    /**
     * 执行任务处理
     * @param array $data 任务参数
     * @return mixed
     */
    public function execute(array $data = [])
    {
    }

}