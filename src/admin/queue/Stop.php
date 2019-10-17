<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace think\admin\queue;

use library\Process;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 平滑停止异步任务守护的主进程
 * Class Stop
 * @package app\admin\queue\task
 */
class Stop extends Command
{

    /**
     * 指令属性配置
     */
    protected function configure()
    {
        $this->setName('xtask:stop')->setDescription('[控制]平滑停止所有的异步任务进程');
    }

    /**
     * 停止所有任务执行
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $command = Process::think('xtask:');
        if (count($result = Process::query($command)) < 1) {
            $output->writeln("没有需要结束的任务进程哦！");
        } else foreach ($result as $item) {
            Process::close($item['pid']);
            $output->writeln("发送结束任务进程{$item['pid']}指令成功！");
        }
    }
}
