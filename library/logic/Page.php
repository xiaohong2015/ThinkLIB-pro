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
// | github开源项目：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace library\logic;

use think\Db;

/**
 * 列表处理管理器
 * Class Page
 * @package library\logic
 */
class Page extends Logic
{
    /**
     * 集合分页记录数
     * @var integer
     */
    protected $total;

    /**
     * 是否启用分页
     * @var boolean
     */
    protected $isPage;

    /**
     * 是否渲染模板
     * @var boolean
     */
    protected $isDisplay;

    /**
     * ViewList constructor.
     * @param string $dbQuery 数据库查询对象
     * @param boolean $isPage 是否启用分页
     * @param boolean $isDisplay 是否渲染模板
     * @param boolean $total 集合分页记录数
     */
    public function __construct($dbQuery, $isPage = true, $isDisplay = true, $total = false)
    {
        $this->total = $total;
        $this->isPage = $isPage;
        $this->isDisplay = $isDisplay;
        parent::__construct($dbQuery);
    }

    /**
     * 应用初始化
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function init()
    {
        if ($this->request->isPost()) {
            $this->_sort();
        }
        return $this->_page();
    }

    /**
     * 列表集成处理方法
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _page()
    {
        // 未配置 order 规则时自动按 sort 字段排序
        if (!$this->db->getOptions('order') && method_exists($this->db, 'getTableFields')) {
            in_array('sort', $this->db->getTableFields()) && $this->db->order('sort asc');
        }
        // 列表分页及结果集处理
        if ($this->isPage) {
            // 分页每页显示记录数
            $limit = intval($this->request->get('limit', cookie('page-limit')));
            cookie('page-limit', $limit = $limit >= 10 ? $limit : 20);

            list($rows, $query) = [[], $this->request->get()];
            $page = $this->db->paginate($limit, $this->total, ['query' => $query]);

            foreach ([10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200] as $num) {
                list($query['limit'], $query['page'], $selected) = [$num, '1', $limit === $num ? 'selected' : ''];
                $url = url('@admin') . '#' . $this->request->baseUrl() . '?' . urldecode(http_build_query($query));
                $rows[] = "<option data-num='{$num}' value='{$url}' {$selected}>{$num}</option>";
            }
            $select = "<select onchange='location.href=this.options[this.selectedIndex].value' data-auto-none>" . join('', $rows) . "</select>";
            $html = "<div class='pagination-container nowrap'><span>共 {$page->total()} 条记录，每页显示 {$select} 条，共 {$page->lastPage()} 页当前显示第 {$page->currentPage()} 页。</span>{$page->render()}</div>";
            $this->class->assign('pagehtml', preg_replace('|href="(.*?)"|', 'data-open="$1"', $html));
            $result = ['page' => ['limit' => intval($limit), 'total' => intval($page->total()), 'pages' => intval($page->lastPage()), 'current' => intval($page->currentPage())], 'list' => $page->items()];
        } else {
            $result = ['list' => $this->db->select()];
        }
        if (false !== $this->class->_callback('_page_filter', $result['list'])) {
            if ($this->isDisplay) {
                return $this->class->fetch('', $result);
            }
        }
        return $result;
    }

    /**
     * 列表排序操作
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function _sort()
    {
        if ($this->request->post('action') === 'resort') {
            foreach ($this->request->post() as $key => $value) {
                if (preg_match('/^_\d{1,}$/', $key) && preg_match('/^\d{1,}$/', $value)) {
                    list($where, $update) = [['id' => trim($key, '_')], ['sort' => $value]];
                    if (false === Db::table($this->db->getTable())->where($where)->update($update)) {
                        $this->class->error('排序失败, 请稍候再试！');
                    }
                }
            }
            $this->class->success('排序成功, 正在刷新页面！', '');
        }
    }

}