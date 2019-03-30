<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://library.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace library\command\update;

use think\console\Command;

/**
 * Class Composer
 * @package library\command
 */
class Composer extends Command
{

    protected $bin;

    protected function configure()
    {
        $this->bin = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer.phar';
        $this->setName('xsync:composer')->setDescription('Synchronize update composer plugs');
    }

    protected function execute(\think\console\Input $input, \think\console\Output $output)
    {
//        $dirs = [env('VENDOR_PATH'), env('RUNTIME_PATH'), env('THINK_PATH')];
//        foreach ($dirs as $dir) if (strlen($dir) < 10) return;
//        if ($this->isWin()) {
//            passthru("rmdir /s/q " . join(' ', $dirs));
//        } else {
//            passthru("rm -rf " . join(' ', $dirs));
//        }
        passthru(PHP_BINARY . " {$this->bin} update --profile --prefer-dist --optimize-autoloader");
    }

    protected function isWin()
    {
        return PATH_SEPARATOR === ';';
    }
}