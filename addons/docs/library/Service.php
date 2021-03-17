<?php

namespace addons\docs\library;

use fast\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\Cache;
use think\Config;
use think\Exception;
use think\View;
use ZipArchive;

/**
 * 文档Service
 */
class Service
{

    public static function getDocsDir()
    {
        return ADDON_PATH . 'docs' . DS;
    }

    public static function getDocsSourceDir()
    {
        return self::getDocsDir() . 'source' . DS;
    }

    public static function getDocsPackageDir()
    {
        return self::getDocsDir() . 'package' . DS;
    }

    public static function getDocsAssetsDir()
    {
        return self::getDocsDir() . 'assets' . DS;
    }

    public static function getDocsDistDir()
    {
        return self::getDocsDir() . 'dist' . DS;
    }

    /**
     * 获取仓库信息
     * @param string $url
     * @return array
     * @throws Exception
     */
    public static function getRepositoryInfo($url)
    {
        $config = get_addon_config('docs');
        $parseArr = parse_url($url);
        if ($config['mode'] == 'package' && $parseArr['host'] !== 'github.com') {
            throw new Exception("压缩包模式只支持Github");
        }
        $pathArr = explode('/', $parseArr['path']);
        $project = end($pathArr);
        array_pop($pathArr);
        $username = end($pathArr);
        $branch = $config['branch'];
        return ['project' => $project, 'username' => $username, 'branch' => $branch];
    }

    /**
     * 远程下载压缩包
     *
     * @param string $url 下载地址
     * @param string $file 存储本地文件
     * @return string
     * @throws Exception
     */
    public static function download($url, $file)
    {
        $options = [
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ];
        $ret = Http::sendRequest($url, [], 'GET', $options);
        if ($ret['ret']) {
            if (substr($ret['msg'], 0, 1) == '{' || substr($ret['msg'], 0, 1) == '<') {
                //下载返回错误，抛出异常
                throw new Exception($ret['msg']);
            }
            if ($write = fopen($file, 'w')) {
                fwrite($write, $ret['msg']);
                fclose($write);
                return $file;
            }
            throw new Exception("没有权限写入临时文件");
        }
        throw new Exception("无法下载远程文件");
    }

    /**
     * 解压插件
     *
     * @return string
     * @throws Exception
     */
    public static function unzip($file)
    {
        $dir = self::getDocsSourceDir();
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($file) !== TRUE) {
                throw new Exception('无法解压文件');
            }
            if (!$zip->extractTo($dir)) {
                $zip->close();
                throw new Exception('无法解压文件');
            }
            $zip->close();
            unlink($file);
            return $dir;
        }
        throw new Exception("无法执行解压操作，请确保ZipArchive安装正确");
    }

    /**
     * 从Github远程下载压缩包
     */
    public static function github()
    {
        $config = get_addon_config('docs');
        $repoInfo = Service::getRepositoryInfo($config['repository']);
        $package = "https://github.com/{$repoInfo['username']}/{$repoInfo['project']}/archive/{$repoInfo['branch']}.zip";

        $packageDir = self::getDocsPackageDir();
        if (!is_dir($packageDir)) {
            mkdir($packageDir, 0755);
        }
        $file = $packageDir . date("YmdHis") . '.zip';
        Service::download($package, $file);
        Service::unzip($file);
    }

    /**
     * 生成HTML
     */
    public static function build()
    {
        $config = get_addon_config('docs');
        $docsDir = self::getDocsDir();

        $docsdata = self::getDocsData();

        $sourceDir = self::getDocsSourceDir();

        $assetsDir = self::getDocsAssetsDir();

        //创建静态文件目录
        $distDir = self::getDocsDistDir();
        rmdirs($distDir);
        mkdir($distDir, 0755);

        // 如果有上传Logo
        if ($config['logo']) {
            if (Config::get('upload.cdnurl')) {
                //如果Logo在远程
                $config['logo'] = Config::get('upload.cdnurl') . $config['logo'];
            } else {
                //如果Logo在本地
                $filename = "logo" . substr($config['logo'], strripos($config['logo'], '.'));
                $dest = copy(ROOT_PATH . 'public' . str_replace('/', DS, $config['logo']), $assetsDir . "images" . DS . $filename);
                $config['logo'] = "__ADDON__/images/{$filename}";
            }
        }

        // 这里必须要清除模板缓存
        rmdirs(TEMP_PATH);

        $view = new View(Config::get('template'), Config::get('view_replace_str'));

        $view->assign("config", $config);
        $view->assign('docsmode', 'html');

        $view->engine->config(['view_path' => $docsDir . 'view' . DS, 'tpl_cache' => false]);
        $view->engine->layout('layout/main');

        $indexpage = Service::getIndexPage($docsdata);

        $view->replace('__ADDON__', './assets');
        $view->replace('__HOMEURL__', "./");
        $view->replace('__INDEXURL__', $config['openindex'] && $config['redirectindex'] ? './' : $indexpage['url']);

        //生成内页
        foreach ($docsdata as $type => $pages) {
            //需要重新调整URL
            $view->replace('__ADDON__', $type == $config['roottype'] ? './assets' : '../assets');
            $view->replace('__HOMEURL__', $type == $config['roottype'] ? './' : '../');
            $view->replace('__INDEXURL__', $type == $config['roottype'] ? './' . $indexpage['url'] : "../" . ($indexpage['type'] == $config['roottype'] ? "" : "{$indexpage['type']}/") . $indexpage['url']);

            $typeDir = $type == $config['roottype'] ? $distDir : $distDir . $type . DS;
            if (!is_dir($typeDir)) {
                mkdir($typeDir, 0755);
            }
            $view->assign('pages', $pages);
            foreach ($pages as $page) {
                $page['content'] = self::html($page['relative']);
                $html = $view->fetch("index/page", ['page' => $page]);
                file_put_contents($typeDir . $page['name'] . '.html', $html);
            }
        }

        //生成首页
        if ($config['openindex']) {
            $page = [
                'name'    => 'index',
                'type'    => 'index',
                'title'   => $config['lang']['index'],
                'url'     => '',
                'index'   => 0,
                'content' => ''
            ];
            $view->replace('__ADDON__', './assets');
            $view->replace('__HOMEURL__', './');
            $view->replace('__INDEXURL__', $indexpage['type'] == $config['roottype'] ? $indexpage['url'] : "{$indexpage['type']}/" . $indexpage['url']);
            $content = $view->fetch("index/index", ['page' => $page]);
            file_put_contents($distDir . $page['name'] . '.html', $content);
        }

        //生成文档JSON数据
        if ($config['opensearch']) {
            $result = [];
            foreach ($docsdata as $type => $pages) {
                foreach ($pages as $index => $page) {
                    $file = $sourceDir . $page['relative'];
                    $content = file_get_contents($file);
                    $result[] = ['title' => $page['title'], 'content' => $content, 'url' => $page['url'], 'type' => $page['type'], 'root' => $page['type'] == $config['roottype'] ? 1 : 0, 'relative' => $page['relative']];
                }
            }
            //生成JSON数据
            file_put_contents(self::getDocsAssetsDir() . 'js' . DS . 'docs.json', json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        //复制文件
        copydirs($assetsDir, $distDir . 'assets');

        //再次清除缓存
        rmdirs(TEMP_PATH);
    }

    /**
     * 获取单个Markdown文件信息
     * @param string $md
     * @param bool $withsource 包含渲染前的源内容
     * @param bool $withcontent 包含渲染后的HTML内容
     * @return array
     */
    public static function getMarkdownData($md, $withsource = false, $withcontent = false)
    {
        $sourceDir = self::getDocsSourceDir();
        if (!is_file($sourceDir . $md)) {
            return null;
        }
        $content = file_get_contents($sourceDir . $md);
        if (!$content) {
            return null;
        }

        $config = get_addon_config('docs');
        $name = basename($md);
        $name = substr($name, 0, -(strlen($config['suffix']) + 1));

        $relative = str_replace(DS, '/', $md);
        $pattern = "/\-{3}(.*?title(.*?))\-{3}/s";
        $data = [
            'title'         => $name,
            'name'          => $name,
            'relative'      => $relative,
            'type'          => $config['roottype'],
            'order'         => 0,
            'isnew'         => 0,
            'url'           => $name . '.html',
            'contributeurl' => str_replace('{relativepath}', $relative, $config['contributeurl']),
            'source'        => '',
            'content'       => '',
            'index'         => 0
        ];
        preg_match_all($pattern, $content, $matches);
        if (isset($matches[1][0])) {
            $arr = explode("\n", $matches[1][0]);
            foreach ($arr as $k => $v) {
                if (stripos($v, ':') !== false) {
                    list($field, $value) = explode(':', $v);
                    $field = trim($field);
                    $value = trim($value);
                    if (!$field || !$value) {
                        continue;
                    }
                    $data[$field] = $value;
                }
            }
        }
        if ($withsource) {
            $data['source'] = preg_replace($pattern, '', $content);
        }
        if ($withcontent) {
            $data['content'] = self::html($md);
        }
        return $data;
    }

    /**
     * 设置Markdata数据
     * @param string $md
     * @param array $data
     * @return boolean
     */
    public static function setMarkdownData($md, $data)
    {
        $source = $data['source'];
        $data = array_intersect_key($data, array_flip(['title', 'order', 'type', 'isnew']));
        unset($data['source']);
        $attr = ['---'];
        foreach ($data as $k => $v) {
            $attr[] = "{$k}:{$v}";
        }
        $attr[] = "---";
        $content = implode("\n", $attr) . "\n\n" . $source;
        $file = self::getDocsSourceDir() . str_replace('/', DS, $md);
        file_put_contents($file, $content);
        self::refreshDocsData();
        return true;
    }

    /**
     * 获取文档基础数据
     * @return array
     */
    public static function getDocsData()
    {
        $docsdata = Cache::get('docsdata');
        if ($docsdata) {
            return $docsdata;
        }
        $config = get_addon_config('docs');
        $addonDir = self::getDocsDir();
        $sourceDir = self::getDocsSourceDir();

        $dir_iterator = new RecursiveDirectoryIterator($sourceDir);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        $files = [];
        foreach ($iterator as $file) {
            if (preg_match("/(.*)\.{$config['suffix']}$/i", $file)) {
                $files[] = $file;
            }
        }
        $docsdata = [];


        $order = 1;
        foreach ($files as $file) {
            $order++;
            $relative = str_replace($sourceDir, '', $file->getPathname());
            $data = self::getMarkdownData($relative);
            if (!$data) {
                continue;
            }
            //如果未定义order排序
            $data['order'] = $data['order'] ? $data['order'] : $order;
            $docsdata[$data['type']][] = $data;
        }

        //将页面按sort升序排序
        foreach ($docsdata as $k => &$v) {
            $v = array_values(self::getArraySortIndex($v, 'order'));
            foreach ($v as $m => &$n) {
                $n['index'] = $m;
            }
            unset($n);
        }
        unset($v);

        //将基础信息缓存起来
        Cache::set("docsdata", $docsdata);
        return $docsdata;
    }

    /**
     * 刷新
     * @return array
     */
    public static function refreshDocsData()
    {
        Cache::rm("docsdata");
        return self::getDocsData();
    }

    /**
     * 解析markdown
     * @param string $md
     * @return string
     */
    public static function html($md)
    {
        $pattern = "/\-{3}(.*?title(.*?))\-{3}/s";
        $sourceDir = self::getDocsSourceDir();
        $content = file_get_contents($sourceDir . $md);
        $content = preg_replace($pattern, '', $content);
        return Markdown::text($content);
    }

    /**
     * 二维数组排序
     * @param array $arr
     * @param string $field
     * @param string $sort
     * @return array
     */
    protected static function getArraySortIndex($arr, $field = 'weigh', $sort = "SORT_ASC")
    {
        $ret = $sorter = array();
        foreach ($arr as $k => $v) {
            $sorter[$k] = $v[$field];
        }

        natcasesort($sorter);
        foreach ($sorter as $k => $v) {
            $ret[$k] = $arr[$k];
        }

        if (strtoupper($sort) == "SORT_DESC") {
            $ret = array_reverse($ret, true);
        }
        return $ret;
    }

    public static function getPageByName($docsdata, $md)
    {
        $page = null;
        foreach ($docsdata as $type => $pages) {
            foreach ($pages as $k => $item) {
                if ($item['relative'] == $md) {
                    $page = $item;
                    break 2;
                }
            }
        }
        return $page;
    }

    public static function getIndexPage(& $docsdata)
    {
        $page = null;
        $list = null;
        $config = get_addon_config('docs');
        $page = self::getPageByName($docsdata, $config['indexdocmd']);
        if (!$page) {
            //如果有从默认分类中读取
            if (isset($docsdata[$config['roottype']])) {
                $list = $docsdata[$config['roottype']];
            }
            if (!$list) {
                foreach ($docsdata as $k => $v) {
                    if ($v) {
                        $list = $v;
                        break;
                    }
                }
            }
            if ($list) {
                $page = array_values($list)[0];
            }
        }
        return $page ? $page : ['name' => '', 'title' => '', 'type' => '', 'type' => '', 'url' => ''];
    }

}
