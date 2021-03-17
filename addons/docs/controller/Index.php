<?php

namespace addons\docs\controller;

use addons\docs\library\Service;
use think\addons\Controller;
use think\Config;
use think\Exception;

/**
 * 文档
 *
 */
class Index extends Controller
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();

        $this->view->engine->layout('layout/main');
    }

    // 文档首页
    public function index()
    {
        $config = get_addon_config('docs');

        //获取md文件
        $md = $this->request->request('md');
        if (!$md) {
            $md = substr($this->request->url(), strlen(str_replace('[:name]', '', $config['rewrite']['index/index'])));
            if ($md) {
                $md .= '.' . $config['suffix'];
            }
        }

        //浏览模式
        $this->view->assign('docsmode', 'server');
        $docurl = addon_url('docs/index/index', [':name' => ''], false);

        $docsdata = Service::getDocsData();
        foreach ($docsdata as &$pages) {
            foreach ($pages as &$item) {
                //变更URL
                //$item['url'] = addon_url("docs/index/index", [':name'=>'', 'md' => $item['relative']],false);
                $path = substr($item['relative'], 0, -(strlen($config['suffix']) + 1));

                $item['url'] = $docurl . $path;
            }
            unset($item);
        }
        unset($pages);
        if (!$docsdata) {
            $this->error("还未创建任何文档", $docurl);
        }
        $indexpage = Service::getIndexPage($docsdata);

        $this->view->replace('__ADDON__', Config::get('site.cdnurl') . "/assets/addons/" . $this->addon);
        $this->view->replace('__HOMEURL__', $docurl);
        $this->view->replace('__INDEXURL__', $indexpage['url']);

        if (!$md) {
            //开启首页访问
            if ($config['openindex']) {
                $page = [
                    'name'    => 'index',
                    'type'    => 'index',
                    'title'   => $config['lang']['index'],
                    'index'   => 0,
                    'url'     => '',
                    'content' => ''
                ];
                return $this->view->fetch("index/index", ['page' => $page, 'pages' => []]);
            } else {
                //如果未指定md,则正动匹配roottype下的index.md
                $md = $indexpage['relative'];
            }
        } else {
            $md = stripos($md, ".md") !== false ? $md : rtrim($md) . "/index.{$config['suffix']}";
        }

        $cache = Service::getPageByName($docsdata, $md);
        if (!$cache) {
            $this->error("未找到指定页面", $docurl);
        }
        $page = Service::getMarkdownData($md, false, true);
        if (!$page) {
            $this->error("未找到指定页面", $docurl);
        }
        //设置index
        $page['index'] = $cache['index'];
        return $this->view->fetch("index/page", ['page' => $page, 'pages' => $docsdata[$page['type']]]);
    }

    /**
     * Webhook接口
     */
    public function api()
    {
        Config::set('default_return_type', 'json');
        try {
            $config = get_addon_config('docs');
            if (!$this->validateSecret($config['secret'])) {
                throw new Exception("验签不通过");
            }

            //如果采用Shell来刷新，可以注释下面两行
            //file_put_contents(ADDON_PATH . 'docs' . DS . 'updated', 1);
            //$this->success("刷新成功!");

            if ($config['mode'] == 'package') {
                Service::github();
            } else {
                $sourceDir = ADDON_PATH . 'docs' . DS . 'source' . DS;
                exec("cd {$sourceDir};git pull -a 2>&1", $output, $exitcode);
                if ($exitcode != 0) {
                    throw new Exception("发生错误：" . json_encode($output));
                }
            }
            Service::build();
            $this->success("刷新成功!");
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 命令行调用生成接口
     */
    public function build()
    {
        Config::set('default_return_type', 'json');
        if (!$this->request->isCli()) {
            $this->error("仅限于命令行执行");
        }
        try {
            $config = get_addon_config('docs');
            if ($config['mode'] == 'package') {
                Service::github();
            } else {
                $sourceDir = ADDON_PATH . 'docs' . DS . 'source' . DS;
                exec("cd {$sourceDir};git pull -a 2>&1", $output, $exitcode);
                if ($exitcode != 0) {
                    throw new Exception("发生错误：" . json_encode($output));
                }
            }
            Service::build();
            $this->success("刷新成功!");
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function validateSecret($secret)
    {
        $payload = file_get_contents('php://input');
        if (strpos($payload, 'payload=') === 0) {
            // 如果是Github
            $payload = substr(urldecode($payload), 8);
        } else {
            // 如果是码云GIT
            $json = json_decode($payload, TRUE);
            if (isset($json['password']) && $json['password'] == $secret) {
                return true;
            }
        }

        $signature = $this->request->server('HTTP_X_HUB_SIGNATURE');
        $event = $this->request->server('HTTP_X_GITHUB_EVENT');
        $delivery = $this->request->server('HTTP_X_GITHUB_DELIVERY');
        if (!isset($signature, $event, $delivery)) {
            return false;
        }
        if (!$this->validateSignature($signature, $payload, $secret)) {
            return false;
        }
        return true;
    }

    protected function validateSignature($signature, $payload, $secret)
    {
        list ($algo, $gitHubSignature) = explode("=", $signature);
        if ($algo !== 'sha1') {
            return false;
        }
        $payloadHash = hash_hmac($algo, $payload, $secret);
        return ($payloadHash === $gitHubSignature);
    }

}
