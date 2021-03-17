<?php

namespace app\admin\controller;

use addons\docs\library\Service;
use app\common\controller\Backend;
use fast\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\Exception;
use think\Validate;
use ZipArchive;

/**
 * 
 * 文档管理
 * @icon fa fa-circle-o
 */
class Docs extends Backend
{

    protected $docs = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->docsdata = Service::getDocsData();
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            $search = $this->request->request("search");
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = [];
            foreach ($this->docsdata as $type => $pages)
            {
                foreach ($pages as $index => $page)
                {
                    if (!$search || stripos($page['relative'], $search) !== false || stripos($page['title'], $search) !== false)
                    {
                        $page['relativeurl'] = addon_url("docs/index/index") . "?md={$page['relative']}";
                        $list[] = $page;
                    }
                }
            }
            $total = count($list);
            $result = array("total" => $total, "rows" => array_slice($list, $offset, $limit));
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                try
                {
                    if (!$params['md'])
                    {
                        throw new Exception("MD文件不能为空");
                    }
                    $config = get_addon_config('docs');
                    //未设置后缀
                    if (stripos($params['md'], ".{$config['suffix']}") === false)
                    {
                        $params['md'] .= ".{$config['suffix']}";
                    }
                    if (mb_substr_count($params['md'], "/") > 1)
                    {
                        throw new Exception("MD文件中只能包含一个/字符");
                    }
                    if (!Validate::is($params['md'], "/^[A-Za-z0-9\-\_\.]+$/"))
                    {
                        throw new Exception("MD文件只能是字母数字下划线");
                    }
                    $row = Service::getMarkdownData($params['md']);
                    if ($row)
                    {
                        throw new Exception("对应的MD文件已经存在");
                    }
                    Service::setMarkdownData($params['md'], $params);
                    $this->success();
                }
                catch (Exception $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = Service::getMarkdownData($ids, true, false);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                try
                {
                    Service::setMarkdownData($ids, $params);
                    $this->success();
                }
                catch (Exception $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $row = Service::getMarkdownData($ids, false, false);
            if (!$row)
                $this->error(__('No Results were found'));
            try
            {
                unlink(Service::getDocsSourceDir() . $row['relative']);
                Service::refreshDocsData();
                $this->success();
            }
            catch (Exception $e)
            {
                $this->error($e->getMessage());
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 刷新
     */
    public function refresh()
    {
        Service::refreshDocsData();
        $this->success();
    }

    /**
     * 导出
     */
    public function export()
    {
        //重新生成一次文档
        Service::build();

        $distDir = Service::getDocsDistDir();
        $zipFile = RUNTIME_PATH . 'addons' . DS . 'export-docs-' . date('YmdHis') . '.zip';

        $rootPath = realpath($distDir);

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file)
        {
            if (!$file->isDir())
            {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        Http::sendToBrowser($zipFile);
    }

    /**
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        return;
    }

}
