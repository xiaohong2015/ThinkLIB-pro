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

use library\command\Update;

/**
 * Class UpdateService
 * @package app\admin\logic\update
 */
class Service extends Update
{
    protected function configure()
    {
        $this->modules = ['application/service/'];
        $this->setName('xsync:service')->setDescription('synchronize update service module files');
    }
}