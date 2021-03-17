<?php

namespace addons\docs;

use app\common\library\Menu;
use think\Addons;

/**
 * 文档生成插件
 */
class Docs extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        $menu = [
            [
                'name'    => 'docs',
                'title'   => '文档管理',
                'icon'    => 'fa fa-file',
                'remark'  => '可在线新增、编辑文档并对生成的HTML进行导出操作',
                'sublist' => [
                    ['name' => 'docs/index', 'title' => '查看'],
                    ['name' => 'docs/add', 'title' => '添加'],
                    ['name' => 'docs/edit', 'title' => '修改'],
                    ['name' => 'docs/del', 'title' => '删除'],
                    ['name' => 'docs/refresh', 'title' => '刷新'],
                    ['name' => 'docs/export', 'title' => '导出'],
                ]
            ]
        ];
        Menu::create($menu);
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        Menu::delete('docs');
        return true;
    }
    
    /**
     * 插件启用方法
     */
    public function enable()
    {
        Menu::enable('docs');
    }

    /**
     * 插件禁用方法
     */
    public function disable()
    {
        Menu::disable('docs');
    }

}
