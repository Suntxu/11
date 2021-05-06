<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Hook;
use think\Validate;
use think\Db;
use app\admin\library\Redis;
use fast\Http;
use app\admin\common\Fun;

/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{

    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['index', 'logout','getDisposeTask','notMoneyNotice'];
    protected $layout = '';

    public function _initialize()
    { 
        parent::_initialize();
    }
    /**
     * 后台首页
     */
    public function index()
    {
        //左侧菜单
        list($menulist, $navlist) = $this->auth->getSidebar([
            'dashboard' => 'hot',
            'addon'     => ['new', 'red', 'badge'],
            'auth/rule' => __('Menu'),
            'general'   => ['new', 'purple'],
        ], $this->view->site['fixedpage']);
        $action = $this->request->request('action');
        if ($this->request->isPost()) {
            if ($action == 'refreshmenu') {
                $this->success('', null, ['menulist' => $menulist, 'navlist' => $navlist]);
            }
        }
       // echo '<pre>';
       // print_R($menulist);
       // die;
        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('title', __('Home'));
        return $this->view->fetch();
    }



    /**
     * 管理员登录
     */
    public function login()
    {
        $url = $this->request->get('url', 'index/index');
        if ($this->auth->isLogin()) {
            $this->success(__("You've logged in, do not login again"), $url);
        }

        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'token',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                '__token__' => $token,
            ];

            if (Config::get('fastadmin.login_captcha')) {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }

            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);

            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            AdminLog::setTitle(__('Login'));
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                $this->error($msg, $url, ['token' => $this->request->token()]);
            }
        }
        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            $this->redirect($url);
        }
        $background = Config::get('fastadmin.login_background');
        $background = stripos($background, 'http') === 0 ? $background : config('site.cdnurl') . $background;
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("admin_login_init", $this->request);
        return $this->view->fetch();
    }
    
    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        Hook::listen("admin_logout_after", $this->request);
        $this->success(__('Logout successful'), '/operate/login');
    }
    
    /**
     * 更新redis配置
     */
    public function upredis(){

        if($this->request->isAjax()){
            $redis = new Redis();
            //存储api信息配置 where('status',1)->
            $api = Db::name('domain_api')->field('showtit,emailau,ifreal,tempid,tit,regid,status,xf_lock,remark,id,region,accessKey,secret,api,tempmethod,flow,tempexp')->select();
            $redis->del('Api_Id');
            $cates = $this->getCates();
            foreach($api as $v){
                $v['regname'] = $cates[$v['regid']];
                $redis->RPush('Api_Id',$v['id']);
                $redis->hMset('Api_Info_'.$v['id'],$v);
            }
            $this->saveApis();

            //存储客服信息
            $service = Db::name('user_service')->alias('s')->join('domain_user u','u.id=s.userid')
                ->field('s.img,s.nickname,s.sex,s.tel,s.qq,s.wx,s.online,s.userid')
                ->where('u.special',1)
                ->select();

            $redis->del('hm_service_list');

            foreach($service as $v){
                $redis->LPush('hm_service_list',$v['userid']);
                $redis->hMset('hm_service_list_'.$v['userid'],$v);
            }
            
            // //分析网站打码的设置
            // $files = Http::get($_SERVER['HTTP_HOST'].'/admin/webconfig/all/index');
            // preg_match_all('/<input id="ymdm[12]" .*?  checked .*? value="(\d)">/',$files,$arr);
           
            // $redis->set('checkdomain_use_dm',$arr[1][0]);

            return ['code' => 0,'msg' => 'redis配置已更新!'];

        }
    }
    /**
     * 获取处理任务提示
     */
    public function getDisposeTask(){
        global $remodi_db;
        $rules = [];
        $gids = $this->auth->getGroups();
        if($gids[0]['rules'] != '*'){
            $rules = $this->auth->getRuleIds();
        }
        //获取角色分组pid
        $groupPids = array_unique(array_column($gids,'pid'));
        $time = time();
        if(empty($rules)){
            $count['shopzt'] = Db::name('storeconfig')->where('shopzt',2)->count();
            $count['tixian'] = Db::name('domain_tixian')->where('zt',4)->count(); 
            $count['tixian_sf'] = Db::name('domain_tixian')->where('zt',5)->count(); 
            $count['real'] = Db::name('user_renzheng')->where('status',0)->count();
            $count['yjtx'] = Db::name('domain_promation_reward_log')->where(['status' => 0])->count();
            $count['task1'] = Db::name('batch_into')->where('audit',0)->count();
            $count['task1_sf'] = Db::name('batch_into')->where('audit',4)->count();
            $count['task2'] = Db::name('domain_orderfx')->where(['fx_stat' => 0,'status' => 1])->count();
            $count['task3'] = Db::name('domain_orderfx')->where('status',0)->count();
            $count['task4'] = Db::name('domain_bill_recode c')->join('domain_bill b','c.bid=b.id')->where(['c.statu'=>0])->count();
            $count['task5'] = Db::name('domain_access')->where(['audit'=>0])->count();

            $count['erp_err'] = Db::connect($remodi_db)->name('erp_outerror')->count();
            // $count['reserve'] = Db::name('domain_order_reserve')->where(['status' => 0,'type' => 0]) ->group('tit')->count();
            
            $count['reserve_order'] = Fun::ini()->getMultDomainReserveNum();
            //怀米大使审核    
            // $count['eelch'] = Db::name('domain_promotion')->where('status',0)->count();
            //专属客服换绑审核
            $count['serve'] = Db::name('exclusive_record')->where('status',0)->count();

            //35互联过过户任务
            // $redis = new Redis();
            // $stids = $redis->lrange('hander_update_contact',0,-1);
            // $update_35 = 0;
            // foreach($stids as $v){
            //     $tapi = $redis->lrange('upinfo_task_all_api_'.$v,0,-1);
            //     if(in_array(30,$tapi)){
            //         $update_35 += 1;
            //     }
            // }
            // //获取已处理的任务数量
            // $ltasid = $redis->lLen('ownership_task_id');
            // $count['update_35'] = ($update_35 - $ltasid);
            //待处理的域名委托
            $count['entrust'] = Db::name('domain_entrust')->where('status',0)->count();

            //待联系的回收域名
            $count['recycle'] = Db::name('domain_recycle')->where('status',0)->count();
            //域名回收提示
            $count['recycle_task'] = Db::name('recycle_task')->where('status',2)->count();
            //手动回收
            $count['recycle'] = Db::name('domain_recycle')->where('status',0)->count();
            //用户注销
            $count['cancel'] = Db::name('domain_user')->where('zt',5)->count();

            //预定域名 超过3个小时
            $count['overtime'] = Db::name('domain_order_reserve')->where($time.' - 10800  > time  and status = 0 and type = 1')->column('tit');

            //获取已到期的禁用用户数量
            $count['disuser_userids'] = Db::name('user_disable_record')->where('type = 1 and flag = 0 and dis_time < '.$time)->column('userid');

            //待审核的提现承诺信息
            $count['txpromise'] = Db::name('tixian_pledge')->where('status',0)->count();

            //待审核的认证换绑记录
            $count['accounts_update'] = Db::name('accounts_update')->where('status',0)->count();

            //待审核的域名属性修改
            $count['domain_attr_update'] = Db::name('domain_pro_trade_update')->where('status',0)->count();

            //域名举报
            $count['domain_report'] = Db::name('domain_report_info')->where('status',0)->count();

        }else{
            if(in_array(440,$rules) && !in_array(12, $groupPids) ){
                $count['shopzt'] = Db::name('storeconfig')->where('shopzt',2)->count();
            }
            if(in_array(302,$rules)){
                $count['tixian'] = Db::name('domain_tixian')->where('zt',4)->count(); 
                $count['tixian_sf'] = Db::name('domain_tixian')->where('zt',5)->count(); 
            }
            if(in_array(323,$rules)  && !in_array(12, $groupPids) ){
                $count['real'] = Db::name('user_renzheng')->where('status',0)->count();
            }
            if(in_array(350,$rules)){
                $count['yjtx'] = Db::name('domain_promation_reward_log')->where(['status' => 0])->count();
            }
            if(in_array(295,$rules)){
                $count['task1'] = Db::name('batch_into')->where('audit',0)->count();
                $count['task1_sf'] = Db::name('batch_into')->where('audit',4)->count();
            }
            if(in_array(172,$rules)){
                $count['task2'] = Db::name('domain_orderfx')->where(['fx_stat' => 0,'status' => 1])->count();
                $count['task3'] = Db::name('domain_orderfx')->where('status',0)->count();
            }
            if(in_array(246,$rules)){
                $count['task4'] = Db::name('domain_bill_recode c')->join('domain_bill b','c.bid=b.id')->where(['c.statu'=>0])->count();
            }
            if(in_array(397,$rules)){
                $count['task5'] = Db::name('domain_access')->where(['audit'=>0])->count();
            }

            if(in_array(610,$rules)){
                $count['reserve_order'] = Fun::ini()->getMultDomainReserveNum();
                // $count['reserve'] = Db::name('domain_order_reserve')->where(['status' => 0,'type' => 0]) ->group('tit')->count();
            }
            if(in_array(496,$rules)){
                $count['serve'] = Db::name('exclusive_record')->where('status',0)->count();
            }
            // if(in_array(510,$rules)){
            //      //35互联过过户任务
            //     $redis = new Redis();
            //     $stids = $redis->lrange('hander_update_contact',0,-1);
            //     $update_35 = 0;
            //     foreach($stids as $v){
            //         $tapi = $redis->lrange('upinfo_task_all_api_'.$v,0,-1);
            //         if(in_array(30,$tapi)){
            //             $update_35 += 1;
            //         }
            //     }
            //     //获取已处理的任务数量
            //     $ltasid = $redis->lLen('ownership_task_id');
            //     $count['update_35'] = ($update_35 - $ltasid);

            // }
            if(in_array(514,$rules)){
                //待处理的域名委托
                $count['entrust'] = Db::name('domain_entrust')->where('status',0)->count();
            }

            if(in_array(665,$rules)){
                //erp出库错误
                $count['erp_err'] = Db::connect($remodi_db)->name('erp_outerror')->count();
            }

            if(in_array(563, $rules)){
                //待联系的回收域名
                $count['recycle'] = Db::name('domain_recycle')->where('status',0)->count();
            }
            if(in_array(566,$rules)){
                //域名回收提示
                $count['recycle_task'] = Db::name('recycle_task')->where('status',2)->count();
            }

            if(in_array(565,$rules)){
                //手动回收
                $count['recycle'] = Db::name('domain_recycle')->where('status',0)->count();
            }
            if(in_array(629,$rules)){
                //用户注销
                $count['cancel'] = Db::name('domain_user')->where('zt',5)->count();
            }
            if(in_array(471,$rules)){
                //预定超过3小时未处理
                $count['overtime'] = Db::name('domain_order_reserve')->where($time.' - 10800  > time  and status = 0 and type = 1')->column('tit');
            }
            if(in_array(642,$rules)){
                //禁用用户记录
                $count['disuser_userids'] = Db::name('user_disable_record')->where('type = 1 and flag = 0 and dis_time < '.$time)->column('userid');
            }
            if(in_array(679,$rules)){
                //待审核的提现承诺信息
                $count['txpromise'] = Db::name('tixian_pledge')->where('status',0)->count();
            }
            if(in_array(691,$rules)){
                //待审核的认证换绑记录
                $count['accounts_update'] = Db::name('accounts_update')->where('status',0)->count();
            }
            if(in_array(730,$rules)){
                //待审核的域名属性修改
                $count['domain_attr_update'] = Db::name('domain_pro_trade_update')->where('status',0)->count();
            }
            if(in_array(502,$rules)){
                //域名举报
                $count['domain_report'] = Db::name('domain_report_info')->where('status',0)->count();
            }

        }
        $groupIds = array_unique(array_column($gids,'group_id'));
        if($this->auth->id == 1 || in_array(12,$groupIds)){
            //注册商余额不足提示
            $apis = $this->getApis();
            $redis = new Redis(['select' => 6]);
            $lmon = array_unique($redis->lRange('API_ACCOUNTS_NOMONEY',0,-1));
            $count['sufficient'] = [];
            foreach($lmon as $v){
                $count['sufficient'][$v] = $apis[$v];
            }
        }


        $count = array_filter($count);
        // if(isset($count['reserve']) && isset($count['reserve_order'])){
        //     $total = count($count) - 1;
        // }else{
        $total = count($count);

        //统计超时域名的数量
        if(isset($count['overtime'])){
            $count['overtime_count'] = count($count['overtime']);
            $count['overtime'] = urlencode(implode("\n",$count['overtime']));
        }
        if(isset($count['disuser_userids'])){
            $count['disuser_count'] = count($count['disuser_userids']);
            $count['disuser_userids'] = implode('|',$count['disuser_userids']);
        }

        // }
        return ['code' => 0,'data' => $count,'total' => $total];
    }

    /**
     * 注册商余额不足处理
     */
    public function notMoneyNotice(){
        if($this->request->isAjax()){
            $api_id = intval($this->request->post('api_id'));
            $redis = new Redis(['select' => 6]);
            $redis->lRem('API_ACCOUNTS_NOMONEY',0,$api_id);
            return ['code' => 0,'msg' => 'ok'];
        }

    }

}
