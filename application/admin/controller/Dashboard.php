<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Db;
use app\admin\library\Redis;
use app\admin\common\Fun;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    public function _initialize()
    {   
        parent::_initialize();
    }
    /**
     * 查看
     */
    public function index()
    {

        $rules = [];
        $gids = $this->auth->getGroups();
        if($gids[0]['rules'] != '*'){
            $rules = $this->auth->getRuleIds();
        }
        //获取角色分组pid
        $groupPids = array_unique(array_column($gids,'pid'));

        if(empty($rules)){
            //获取开店审核统计 314
            $count['userinfo1'] = Db::name('storeconfig')->where('shopzt',2)->count();
            //获取需要处理体现统计 264
            $count['userinfo2'] = Db::name('domain_tixian')->where('zt',4)->count(); 
            $count['tixian_sf'] = Db::name('domain_tixian')->where('zt',5)->count(); 
            //实名认证审核 321
            $count['userinfo3'] = Db::name('user_renzheng')->where('status',0)->count();
            //禁用会员 283
            $count['userinfo4'] = Db::name('domain_user')->where('zt',3)->count();
            //总用户数 283
            $count['userinfo5'] = Db::name('domain_user')->count(); 
            // 获取需要佣金审核的数量 350
            $count['yjtx'] = Db::name('domain_promation_reward_log')->where(['status' => 0])->count();
            //域名信息 
            //所有域名 288
            $count['domain1'] = Db::name('domain_pro_n')->count();
            // 被冻结的域名 288
            $count['domain4'] = Db::name('domain_pro_n')->where(['status'=>4])->count();
            // 正在销售的域名  274
            $count['domain2'] = Db::name('domain_pro_trade')->where(['status'=>1])->count();
            $count['domain3'] = Db::name('domain_pro_trade')->where(['status'=>2])->count();
            //互动信息 
            // $count['new1'] = Db::name('domain_news')->where('zt',2)->count();
            // $count['new2'] = $this->model->returncount('domain_newspj',['zt'=>2]);
            // 184
            $count['new3'] = Db::name('domain_gg')->where('zt != 99 ')->count();
            //需要处理的任务
            $count['task1'] = Db::name('batch_into')->where('audit',0)->count(); // 268
            $count['task1_sf'] = Db::name('batch_into')->where('audit',4)->count(); // 268
            $count['task2'] = Db::name('domain_orderfx')->where(['fx_stat' => 0,'status' => 1])->count(); // 170
            $count['task3'] = Db::name('domain_orderfx')->where('status',0)->count(); // 170
            $count['task4'] = Db::name('domain_bill_recode c')->join('domain_bill b','c.bid=b.id')->where(['c.statu'=>0])->count(); // 245
            $count['task5'] = Db::name('domain_access')->where(['audit'=>0])->count(); // 395
            //push记录
            $count['task6'] =  Db::name('recycle_task')->where('status',2)->count();

            // $count['reserve_order'] = Db::name('domain_order_reserve')->where(['status' => 0,'type' => 0])->count(); // 466
            $count['reserve_order'] = Fun::ini()->getMultDomainReserveNum();

            //域名委托 512
            $entrust = Db::name('domain_entrust')->field('count(if(status=1,1,null)) as m,count(if(status=0,1,null)) as n')->find();
            $count['entrust_0'] = $entrust['n'];
            $count['entrust_1'] = $entrust['m'];
            //用户注销
            $count['cancel'] = Db::name('domain_user')->where('zt',5)->count();
            
            //35互联过过户任务 510
            // $redis = new Redis();
            // $stids = $redis->lrange('hander_update_contact',0,-1);
            // $count['update_35'] = 0;
            // foreach($stids as $v){
            //     $tapi = $redis->lrange('upinfo_task_all_api_'.$v,0,-1);
            //     if(in_array(30,$tapi)){
            //         $count['update_35'] += 1;
            //     }
            // }
            
            $count['recycle'] = Db::name('domain_recycle')->where('status',0)->count();

        }else{
            if(in_array(302,$rules)){
                $count['userinfo2'] = Db::name('domain_tixian')->where('zt',4)->count(); 
                $count['tixian_sf'] = Db::name('domain_tixian')->where('zt',5)->count(); 
            }
            if(in_array(440,$rules)){
                //获取开店审核统计 314
                $count['userinfo1'] = Db::name('storeconfig')->where('shopzt',2)->count();
            }
            if(in_array(323,$rules)){
                $count['userinfo3'] = Db::name('user_renzheng')->where('status',0)->count();
            }
            if(in_array(283,$rules)){
                //禁用会员 283
                $count['userinfo4'] = Db::name('domain_user')->where('zt',3)->count();
                //总用户数 283
                $count['userinfo5'] = Db::name('domain_user')->count(); 
            }
            if(in_array(350,$rules)){
                // 获取需要佣金审核的数量 350
                $count['yjtx'] = Db::name('domain_promation_reward_log')->where(['status' => 0])->count();
            }
            if(in_array(288,$rules)){
                //所有域名 288
                $count['domain1'] = Db::name('domain_pro_n')->count();
                // 被冻结的域名 288
                $count['domain4'] = Db::name('domain_pro_n')->where(['status'=>4])->count();
            }
            if(in_array(274,$rules)){
                // 正在销售的域名  274
                $count['domain2'] = Db::name('domain_pro_trade')->where(['status'=>1])->count();
                $count['domain3'] = Db::name('domain_pro_trade')->where(['status'=>2])->count();
            }
            if(in_array(184,$rules)){
                $count['new3'] = Db::name('domain_gg')->where('zt != 99 ')->count();
            }
            if(in_array(295,$rules)){
                $count['task1'] = Db::name('batch_into')->where('audit',0)->count();
                $count['task1_sf'] = Db::name('batch_into')->where('audit',4)->count(); 
            }
            if(in_array(172,$rules)){
                $count['task2'] = Db::name('domain_orderfx')->where(['fx_stat' => 0,'status' => 1])->count(); // 170
                $count['task3'] = Db::name('domain_orderfx')->where('status',0)->count();
            }
            if(in_array(246,$rules)){
                $count['task4'] = Db::name('domain_bill_recode c')->join('domain_bill b','c.bid=b.id')->where(['c.statu'=>0])->count();
            }
            if(in_array(397,$rules)){
                $count['task5'] = Db::name('domain_access')->where(['audit'=>0])->count(); // 395
            }
            if(in_array(610,$rules)){
                // $count['reserve_order'] = Db::name('domain_order_reserve')->where(['status' => 0,'type' => 0])->count(); // 466
                $count['reserve_order'] = Fun::ini()->getMultDomainReserveNum();
            }
            if(in_array(514,$rules)){
                //域名委托 512
                $entrust = Db::name('domain_entrust')->field('count(if(status=1,1,null)) as m,count(if(status=0,1,null)) as n')->find();
                $count['entrust_0'] = $entrust['n'];
                $count['entrust_1'] = $entrust['m'];
            }
            if(in_array(566,$rules)){
                //域名回收提示
                $count['task6'] = Db::name('recycle_task')->where('status',2)->count();
            }

            if(in_array(565,$rules)){
                //手动回收
                $count['recycle'] = Db::name('domain_recycle')->where('status',0)->count();
            }
            if(in_array(629,$rules)){
                $count['cancel'] = Db::name('domain_user')->where('zt',5)->count();
            }

            // if(in_array(510,$rules)){
            //     //35互联过过户任务 
            //     $redis = new Redis();
            //     $stids = $redis->lrange('hander_update_contact',0,-1);
            //     $count['update_35'] = 0;
            //     foreach($stids as $v){
            //         $tapi = $redis->lrange('upinfo_task_all_api_'.$v,0,-1);
            //         if(in_array(30,$tapi)){
            //             $count['update_35'] += 1;
            //         }
            //     }
            // }
        }
        
        
        $this->view->assign([
            'totaluser'        => 35200,
            'totalviews'       => 219390,
            'totalorder'       => 32143,
            'totalorderamount' => 174800,
            'todayuserlogin'   => 321,
            'todayusersignup'  => 430,
            'todayorder'       => 2324,
            'unsettleorder'    => 132,
            'sevendnu'         => '80%',
            'sevendau'         => '32%',
            'count'            => $count,
            'groupPids'        => $groupPids,
        ]);
        return $this->view->fetch();
    }

}