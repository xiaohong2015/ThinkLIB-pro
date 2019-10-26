<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace think\admin\helper;

use think\admin\Controller;
use think\App;
use think\Db;
use think\db\Query;

/**
 * 基础管理器
 * Class Helper
 * @package think\admin\helper
 */
class Helper
{

    /**
     * 当前应用容器
     * @var App
     */
    public $app;

    /**
     * 数据库实例
     * @var Query
     */
    protected $query;

    /**
     * 当前控制器实例
     * @var Controller
     */
    public $controller;

    /**
     * Helper constructor.
     * @param App $app
     * @param Controller $controller
     */
    public function __construct(Controller $controller, App $app)
    {
        $this->app = $app;
        $this->controller = $controller;
    }

    /**
     * 获取数据库查询对象
     * @param string|Query $dbQuery
     * @return Query
     */
    protected function buildQuery($dbQuery)
    {
        return is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery;
    }

    /**
     * 实例对象反射
     * @param Controller $controller
     * @param App $app
     * @return $this
     */
    public static function instance(Controller $controller, App $app)
    {
        return new static($controller, $app);
    }

}
