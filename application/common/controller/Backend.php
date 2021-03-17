<?php

namespace app\common\controller;

use app\admin\library\Auth;
use think\Config;
use think\Controller;
use think\Hook;
use think\Lang;
use think\Session;
use think\Db;
use app\admin\library\Redis;

/**
 * 后台控制器基类
 */
class Backend extends Controller
{
    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 布局模板
     * @var string
     */
    protected $layout = 'default';

    /**
     * 权限控制类
     * @var Auth
     */
    protected $auth = null;

    /**
     * 模型对象
     * @var \think\Model
     */
    protected $model = null;

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'id';

    /**
     * 是否是关联查询
     */
    protected $relationSearch = false;

    /**
     * 是否开启数据限制
     * 支持auth/personal
     * 表示按权限判断/仅限个人
     * 默认为禁用,若启用请务必保证表中存在admin_id字段
     */
    protected $dataLimit = false;

    /**
     * 数据限制字段
     */
    protected $dataLimitField = 'admin_id';

    /**
     * 数据限制开启时自动填充限制字段值
     */
    protected $dataLimitFieldAutoFill = true;

    /**
     * 是否开启Validate验证
     */
    protected $modelValidate = false;

    /**
     * 是否开启模型场景验证
     */
    protected $modelSceneValidate = false;

    /**
     * Multi方法可批量修改的字段
     */
    protected $multiFields = 'status';

    /**
     * Selectpage可显示的字段
     */
    protected $selectpageFields = '*';
    /**
     * 导入文件首行类型
     * 支持comment/name
     * 表示注释或字段名
     */
    protected $importHeadType = 'comment';

    /**
     * 引入后台控制器的traits
     */
    use \app\admin\library\traits\Backend;

    public function _initialize()
    {
        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());
        // echo $actionname;die;
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;

        // 定义是否Addtabs请求
        !defined('IS_ADDTABS') && define('IS_ADDTABS', input("addtabs") ? TRUE : FALSE);

        // 定义是否Dialog请求
        !defined('IS_DIALOG') && define('IS_DIALOG', input("dialog") ? TRUE : FALSE);

        // 定义是否AJAX请求
        !defined('IS_AJAX') && define('IS_AJAX', $this->request->isAjax());

        $this->auth = Auth::instance();

        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //检测是否登录
            if (!$this->auth->isLogin()) {
                Hook::listen('admin_nologin', $this);
                $url = Session::get('referer');
                $url = $url ? $url : $this->request->url();
                $this->error(__('Please login first'), url('/operate/login', ['url' => $url]));
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    Hook::listen('admin_nopermission', $this);
                    $this->error(__('You have no permission'), '');
                }
            }
        }
        
        // 非选项卡时重定向
        if (!$this->request->isPost() && !IS_AJAX && !IS_ADDTABS && !IS_DIALOG && input("ref") == 'addtabs') {
            $url = preg_replace_callback("/([\?|&]+)ref=addtabs(&?)/i", function ($matches) {
                return $matches[2] == '&' ? $matches[1] : '';
            }, $this->request->url());
            if (Config::get('url_domain_deploy')) {
                if (stripos($url, $this->request->server('SCRIPT_NAME')) === 0) {
                    $url = substr($url, strlen($this->request->server('SCRIPT_NAME')));
                }
                $url = url($url, '', false);
            }
            $this->redirect('index/index', [], 302, ['referer' => $url]);
            exit;
        }

        // 设置面包屑导航数据
        $breadcrumb = $this->auth->getBreadCrumb($path);
        array_pop($breadcrumb);
        $this->view->breadcrumb = $breadcrumb;

        // 如果有使用模板布局
        if ($this->layout) {
            $this->view->engine->layout('layout/' . $this->layout);
        }

        // 语言检测
        $lang = strip_tags($this->request->langset());

        $site = Config::get("site");

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        $imgUrl = IMGURL;

        // 配置信息
        $config = [
            'site'           => array_intersect_key($site, array_flip(['name', 'indexurl', 'cdnurl', 'version', 'timezone', 'languages'])),
            'upload'         => $upload,
            'modulename'     => $modulename,
            'controllername' => $controllername,
            'actionname'     => $actionname,
            'jsname'         => 'backend/' . str_replace('.', '/', $controllername),
            'moduleurl'      => rtrim(url("/{$modulename}", '', false), '/'),
            'language'       => $lang,
            'fastadmin'      => Config::get('fastadmin'),
            'referer'        => Session::get("referer"),
            'operate_url'    => IMGURL_OPERATE,
            'users_url'    => $imgUrl,
        ];
        
        $config = array_merge($config, Config::get("view_replace_str"));

        Config::set('upload', array_merge(Config::get('upload'), $upload));

        // 配置信息后
        Hook::listen("config_init", $config);
        //加载当前控制器语言包
        $this->loadlang($controllername);
        //渲染站点配置
        $this->assign('site', $site);
        //渲染配置信息
        $this->assign('config', $config);
        //渲染权限对象
        $this->assign('auth', $this->auth);
        //渲染管理员对象
        $this->assign('admin', Session::get('admin'));
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 渲染配置信息
     * @param mixed $name 键名或数组
     * @param mixed $value 值
     */
    protected function assignconfig($name, $value = '')
    {
        $this->view->config = array_merge($this->view->config ? $this->view->config : [], is_array($name) ? $name : [$name => $value]);
    }
    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed $searchfields 快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 0);
        $filter = (array)json_decode($filter, TRUE);
        $op = (array)json_decode($op, TRUE);
        $filter = $filter ? $filter : [];

        if(!isset($filter['group'])){
            $group = '';
        }else{
            $group = $filter['group'];
            unset($filter['group']);
        }
        $where = [];
        $tableName = '';
        if ($relationSearch) {
            if (!empty($this->model)) {
                $name = \think\Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
                $tableName = $name . '.';
            } 
            $sortArr = explode(',', $sort);
           
            foreach ($sortArr as $index => & $item) {
                $item = stripos($item, ".") === false ? $tableName . trim($item) : $item;
            }
            unset($item);
            $sort = implode(',', $sortArr);
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$tableName . $this->dataLimitField, 'in', $adminIds];
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        // 返回特殊条件的值
        $special_condition = '';
        $spec = '';
        $special_status = '';
        foreach ($filter as $k => $v) {
            $v = !is_array($v) ? trim($v,'      ') : $v;
            if($k == 'special_status'){
                $special_status = $v;
                continue;
            }
            if($k == 'spec'){
                $spec = $v;
                continue;
            }
            if($k == 'special_condition'){
                $special_condition = $v;
                continue;
            }
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $tableName . $k;
            }
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            switch ($sym) {
                case '=': 
                case '!=':
                    // 或者关系
                    if(strpos($v,'|')){
                        $v = array_map('trim',explode('|',$v));
                    }else{
                        $v = preg_replace('/\s+/is', '',$v);
                    }
                    if(is_array($v)){
                        $where[] = [$k,'in',$v];
                    }else{
                        if($v == 'exists'){
                            $where[] = [$k,'<>',''];
                        }elseif($v == 'notexists'){
                            $where[] = [$k,'=',''];
                        }elseif($v == 'null'){
                            $where[] = $k.' isnull ';
                        }elseif($v == 'notnull'){
                            $where[] = $k.' isnotnull ';
                        }else{
                            $where[] = [$k, $sym, $v];
                        }
                    }
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, preg_replace('/\s+/is','',str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case 'RLIKE':
                    $where[] = [$k, preg_replace('/\s+/is','',str_replace('%...%', '', 'like')), "{$v}%"];
                    break;
                case 'THOUSANDS': //千分位 区间搜索
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $arr = intval($arr[1])*100;
                    } else if ($arr[1] === '') {
                        $arr = intval($arr[0])*100;
                    }
                    $where[] = [$k, 'BETWEEN', array_map(function($v){return intval($v)*100;},$arr)];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, preg_replace('/\s+/is','',intval($v))];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $where[] = "FIND_IN_SET('{$v}', " . ($this->relationSearch ? $k : '`' . str_replace('.', '`.`', $k) . '`') . ")";
                    break;
                case 'TEXT':
                    // 回车专属
                    $TextAv=str_replace(["\r"," "],"",$v);
                    $Text=preg_split("/\n/",$TextAv);
//                    $Text = preg_replace('/\s+/','',$Text);
                    $Text = preg_replace('/\s+/is','',array_filter($Text));
                    if(count($Text) > 300){
                        $Text = array_splice($Text,0,300);
                    }
                    $where[] = [$k, 'in', $Text];
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)' :
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                    
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = preg_replace('/\s+/is','',array_slice(explode(',', $v), 0, 2));
                    if (stripos($v, ',') === false)
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } else if ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }

                    $where[] = [$k, $sym, $arr];

                    break;
                case 'INT':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);

                    // if (stripos($v, ',') === false || !array_filter($arr))
                    if (!array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if (empty($arr[0])) {
                        $sym = $sym == 'INT' ? '<=' : '>=';
                        $arr = strtotime($arr[1]);
                    } else if (empty($arr[1])) {
                        $sym = $sym == 'INT' ? '>=' : '<=';
                        $arr = strtotime($arr[0]);
                    }else{
                        $arr = array_map('strtotime',$arr);
                    }
                    $where[] = [$k, str_replace('INT', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (!array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if (empty($arr[0])) {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } else if (empty($arr[1])) {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function ($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    // 特殊条件过滤掉 交给子类方法处理
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit,$group,$special_condition,$spec,$special_status];
    }

    /**
     * 获取数据限制的管理员ID
     * 禁用数据限制时返回的是null
     * @return mixed
     */
    protected function getDataLimitAdminIds()
    {
        if (!$this->dataLimit) {
            return null;
        }
        if ($this->auth->isSuperAdmin()) {
            return null;
        }
        $adminIds = [];
        if (in_array($this->dataLimit, ['auth', 'personal'])) {
            $adminIds = $this->dataLimit == 'auth' ? $this->auth->getChildrenAdminIds(true) : [$this->auth->id];
        }
        return $adminIds;
    }

    /**
     * Selectpage的实现方法
     *
     * 当前方法只是一个比较通用的搜索匹配,请按需重载此方法来编写自己的搜索逻辑,$where按自己的需求写即可
     * 这里示例了所有的参数，所以比较复杂，实现上自己实现只需简单的几行即可
     *
     */
    protected function selectpage()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);

        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array)$this->request->request("q_word/a");
        //当前页
        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize");
        //搜索条件
        $andor = $this->request->request("andOr", "and", "strtoupper");
        //排序方式
        $orderby = (array)$this->request->request("orderBy/a");
        //显示的字段
        $field = $this->request->request("showField");
        //主键
        $primarykey = $this->request->request("keyField");
        //主键值
        $primaryvalue = $this->request->request("keyValue");
        //搜索字段
        $searchfield = (array)$this->request->request("searchField/a");
        //自定义搜索条件
        $custom = (array)$this->request->request("custom/a");
        $order = [];
        foreach ($orderby as $k => $v) {
            $order[$v[0]] = $v[1];
        }
        $field = $field ? $field : 'name';

        //如果有primaryvalue,说明当前是初始化传值
        if ($primaryvalue !== null) {
            $where = [$primarykey => ['in', $primaryvalue]];
        } else {
            $where = function ($query) use ($word, $andor, $field, $searchfield, $custom) {
                $logic = $andor == 'AND' ? '&' : '|';
                $searchfield = is_array($searchfield) ? implode($logic, $searchfield) : $searchfield;
                foreach ($word as $k => $v) {
                    $query->where(str_replace(',', $logic, $searchfield), "like", "%{$v}%");
                }
                if ($custom && is_array($custom)) {
                    foreach ($custom as $k => $v) {
                        $query->where($k, '=', $v);
                    }
                }
            };
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = [];
        $total = $this->model->where($where)->count();
        if ($total > 0) {
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $datalist = $this->model->where($where)
                ->order($order)
                ->page($page, $pagesize)
                ->field($this->selectpageFields)
                ->select();
            foreach ($datalist as $index => $item) {
                unset($item['password'], $item['salt']);
                $list[] = [
                    $primarykey => isset($item[$primarykey]) ? $item[$primarykey] : '',
                    $field      => isset($item[$field]) ? $item[$field] : ''
                ];
            }
        }
        //这里一定要返回有list这个字段,total是可选的,如果total<=list的数量,则会隐藏分页按钮
        return json(['list' => $list, 'total' => $total]);
    }
    
    //获取指定分类列表
    protected function getcategory()
    {
        $type = $this->request->request("type");
        $xz = empty($this->request->request("xz")) ? '' : $this->request->request("xz");
        //只获取子类目
        if($xz == 'child'){
            $getZ = 'pid != 0';
        }elseif($xz == 'parent'){
            //只获取父类
            $getZ = 'pid = 0';
        }else{
            $getZ = '';
        }
        $list = model('category')->where(['type' => $type])->where($getZ)->order('weigh desc')->select();
        $list = collection($list)->toArray();
        return json($list);
    }
    //获取渠道下拉框名称
    protected function getSelectName()
    {
        $data = Db::name('spread_channel')->field('id,name')->where(['status'=>'normal'])->select();
        return json($data);
    }
    public function getPageList()
    {

        //设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);

        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array)$this->request->request("q_word/a");
        //当前页
        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize");
        //搜索条件
        $andor = $this->request->request("andOr", "and", "strtoupper");

        //显示的字段
        $field = $this->request->request("showField");
        //主键
        $primarykey = $this->request->request("keyField");
        //主键值
        $primaryvalue = $this->request->request("keyValue");
        //自定义搜索条件
        $custom = (array)$this->request->request("custom/a");

        $tableName = $this->request->request('table','domain_user');

        $searchfield = (array)$this->request->request("searchField/a");

        $order = [];
        $where = function ($query) use ($word, $andor, $field, $searchfield, $custom) {
            $logic = $andor == 'AND' ? '&' : '|';
            $searchfield = is_array($searchfield) ? implode($logic, $searchfield) : $searchfield;
            foreach ($word as $k => $v) {
                $query->where(str_replace(',', $logic, $searchfield), "like", "%{$v}%");
            }
            if ($custom && is_array($custom)) {
                foreach ($custom as $k => $v) {
                    if($k != 'type')
                        $query->where($k, '=', $v);
                }
            }
        };
        $list = [];
        $total = Db::name($tableName)->where($where)->count();
        if ($total > 0) {
            $list = Db::name($tableName)->where($where)
                ->order($order)
                ->page($page, $pagesize)
                ->field($primarykey.' as id,'.$field)
                ->select();
        }
        //这里一定要返回有list这个字段,total是可选的,如果total<=list的数量,则会隐藏分页按钮
        return json(['list' => $list, 'total' => $total]);
    }

    //活动拼团操作日志
    public function booking_log($tid,$log,$type,$admin_id,$admin_name)
    {
        $insert['tid']  =   $tid;
        $insert['log']  =   $log;

        $insert['type']  =   $type;

        $insert['admin_id']  =   $admin_id;

        $insert['admin_name']  =   $admin_name;

        $insert['created_at']  =   date('Y-m-d  H:i:s');

        Db::name('assemble_team_log')->insert($insert);

    }
    /**
     * 根据类型获取所有分类
     */
    public function getCates($type = 'api',$flag = true){

        $where['status'] = 'normal';
        if($type){
            $where['type'] = $type;
        }
        $cates = Db::name('category')->field('id,name')->where($where)->select();
        if($flag){
            $cates = array_combine(array_column($cates,'id'),array_column($cates,'name'));
        }
        return $cates;
    }

    /**
     * 获取api信息
     * @param  $regid 注册商id 如果等于-1 获取全部的api信息
     * @return [type]        [description]
     */
    public function getApis($regid = ''){
        $redis = new Redis();
        $api_list = [];
        $api = $redis->lRange('Api_Id',0,-1);
        foreach($api as $v){
            $api_list[$v] = $redis->hGetAll('Api_Info_'.$v);
        }


        //拼接需要后台单独展示的api
        $api_list[1000] = ['id' => 1000,'tit' => '本站','regid' => 0,'showtit' => '本站','tempid' => '','emailau' => '','ifreal' => '','status' => '','regname' => '--'];
        
        if($regid == ''){
            
            $apis = array_combine(array_column($api_list,'id'),array_column($api_list,'tit'));
            return $apis;

        }elseif($regid == -1){

            return $api_list;

        }else{ //获取注册商下面的api ID

            $aids = [];
            foreach($api_list as $v){
                if($v['regid'] == $regid){
                    $aids[] = $v['id'];
                }
            }
            //被禁用的api
            if(empty($aids)){
                $aids = Db::name('domain_api')->where('regid',$regid)->column('id');
            }

            return $aids;
        }
    }

    /**
     * 根据主任务ID获取子任务年份表
     */
    protected function getTaskYear($taskid){

        //提交任务时间 小于 2019-12-31 09:30:00 读取2019的表
        $taskTime = Db::table(PREFIX.'Task_record')->where('id',$taskid)->value('createtime');
        if($taskTime < strtotime('2019-12-31 09:30:00')){
            $year = '_2019';
        }else{
            $year = '';
        }
        return $year;
    }
    /**
     * 存储新的api
     */
    protected function saveApis($param = [],$ids = []){
        $def = '1 = 1 ';
        if($ids){
            $def .= ' and id not in ('.implode(',', $ids).') ';
        }
        $apis = Db::name('domain_api')->where($def)->select();
        if($param){
            $apis = array_merge($apis,[$param]);
        }
       
        $zcslist = $this->getCates();
        $data = [];
        foreach($apis as $v){
            $v['regname'] = $zcslist[$v['regid']];
            $data[$v['id']] = $v;
        }

        if($data){
            $redis = new Redis();
            $redis->set('domain_registrar_list_josn',json_encode($data));
            return true;
        }
        return false;
    }

    /**
     * 记录表生成记录个数
     * @tablename 表前半部分名字
     * @flag 今年的表是否过滤  默认过滤今年的表
     */
    protected function getRecordYearTableName($tablename,$flag = true){
        global $remodi_db;
        $tableName = Db::connect($remodi_db)->table('information_schema.tables')
            ->where('TABLE_SCHEMA',$remodi_db['database'])
            ->where('TABLE_NAME','like',''.PREFIX.$tablename.'%')
            ->column('TABLE_NAME');

        $year = [];
        $y = date('Y');
        foreach($tableName as $v){
            $c = explode('_',$v);
            if($flag && $y == $c){
                continue;
            }
            $year[] = $c[count($c) - 1];
        }
        rsort($year);
        return $year;
    }

    /**
     * 获取渠道名称
     */
    protected function getAdminNickname(){

        $aInfo = Db::name('admin')->field('id,nickname')->where('status','normal')->column('id','nickname');

        return $aInfo;

    }

    /**
     * 获取渠道
     */
    protected function getChannelName(){

        //获取聚到
        $sp = $this->getSelectName()->getData();
        $sparr = array_combine(array_column($sp,'id'),array_column($sp,'name'));
        return $sparr;
    }


}
