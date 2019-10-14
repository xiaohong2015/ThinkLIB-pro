<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://library.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace library\storage;

use library\Storage;

/**
 * 本地服务文件存储支持
 * Class LocalStorage
 * @package library\storage
 */
class LocalStorage extends Storage
{

    /**
     * LocalStorage constructor.
     */
    public function __construct()
    {
        $this->root = rtrim(env('root_path'), '\\/');
    }

    /**
     * 文件储存在本地
     * @param string $name 文件名称
     * @param string $content 文件内容
     * @param boolean $safe 安全模式
     * @return array|null
     * @throws \think\Exception
     */
    public function set($name, $content, $safe = false)
    {
        try {
            $file = $this->path($name, $safe);
            file_exists(dirname($file)) || mkdir(dirname($file), 0755, true);
            if (file_put_contents($file, $content)) return $this->info($name, $safe);
        } catch (\Exception $e) {
            throw new \think\Exception("本地文件存储失败，{$e->getMessage()}");
        }
        return null;
    }

    /**
     * 根据文件名读取文件内容
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function get($name, $safe = false)
    {
        if (!$this->has($name, $safe)) return '';
        return file_get_contents($this->path($name, $safe));
    }

    /**
     * 删除存储的文件
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean|null
     */
    public function del($name, $safe = false)
    {
        if ($this->has($name, $safe)) {
            return @unlink($this->path($name, $safe));
        } else {
            return false;
        }
    }

    /**
     * 检查文件是否已经存在
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function has($name, $safe = false)
    {
        return file_exists($this->path($name, $safe));
    }

    /**
     * 获取文件当前URL地址
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean|string|null
     */
    public function url($name, $safe = false)
    {
        if ($safe) return null;
        $root = rtrim(dirname(request()->basefile(true)), '\\/');
        return "{$root}/upload/{$name}";
    }

    /**
     * 获取文件存储路径
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function path($name, $safe = false)
    {
        $path = $safe ? 'safefile' : 'public/upload';
        return str_replace('\\', '/', "{$this->root}/{$path}/{$name}");
    }

    /**
     * 获取文件存储信息
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return array|null
     */
    public function info($name, $safe = false)
    {
        return $this->has($name, $safe) ? [
            'file' => $this->path($name, $safe), 'url' => $this->url($name, $safe),
            'hash' => md5_file($this->path($name, $safe)), 'key' => "upload/{$name}",
        ] : null;
    }

}