<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
use app\admin\common\sendMail;
use think\Exception;


/**
 * 全部店铺列表
 *
 * @icon fa fa-user
 */
class Shoplist extends Backend
{
     protected $noNeedRight = ['updatePm'];
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$paytime,$account) = $this->buildparams();
            $def = ' 1 = 1 ';
            if($paytime){
                $times = explode(' - ',$paytime);
                $def .= ' and paytime between "'.$times[0].'" and  "'.$times[1].'"';
            }
            $acc = '';
            if($account){
                $userid = Db::name('store_account')->where('account',trim($account))->value('userid');
                $userid = $userid ? $userid : -1;
                $acc .= 't1.userid = '.$userid;

            }

            //根据条件统计总金额
            $field = '(select count(*) from '.PREFIX.'domain_pro_trade where status=1 and userid = t1.userid) as sellernum,sum(if(`status` = 1,money,null)) as sellermoney,count(if(`status` = 1,1,null)) as ysellernum';
            $total = Db::name('storeconfig')->alias('t1')->join('domain_user t3','t1.userid=t3.id')->join('domain_order t2','t3.id=t2.selleruserid','left')->where($where)->where($acc)->where($def)->group('t1.userid')->count();

            $list = Db::name('storeconfig')->alias('t1')->join('domain_user t3','t1.userid=t3.id')->join('domain_order t2','t3.id=t2.selleruserid','left')
                ->field('t1.account,t1.userid,t3.uid,t1.shopzt,t1.shopname,t1.pm,t2.paytime,t1.flag,t1.deposit,'.$field)
                ->where($where)->where($def)->where($acc)->group('t1.userid')
                ->order($sort,$order)->limit($offset,$limit)
                ->select();

            $userids = array_column($list,'userid');
            $buynum = Db::name('domain_order')->where('status',1)->where($def)->whereIn('selleruserid',$userids)->group('selleruserid')->column('count(distinct userid)','selleruserid');
            $sql = $this -> setWhere();

            if(strlen($sql) == 12){
                $ge = "select count(*) as n,sum(money) as znum,count(distinct userid) as zbuy from ".PREFIX."domain_order where status=1 and ".$def;
            }else{
                $ge = "select count(*) as n,sum(t2.money) as znum,count(distinct t2.userid) as zbuy from ".PREFIX."storeconfig as t1 inner join  ".PREFIX."domain_user t3 on t1.userid = t3.id left JOIN ".PREFIX."domain_order as t2 on t1.userid = t2.selleruserid {$sql} and t2.status=1 and".$def;
            }
            $geshu = Db::query($ge);
            $fun = Fun::ini();
            foreach ($list as $k => &$v) {
                $v['t1.shopname'] = $v['shopname'];
                $v['t1.userid'] = $v['userid'];
                $v['t1.deposit'] = $v['deposit'];
                $v['id'] = $v['userid'];
                $v['t3.uid'] = $v['uid'];
                $v['group'] = $v['paytime'];
//                $v['sellernum'] = isset($sellernum[$v['userid']]) ? $sellernum[$v['userid']] : 0;
                $v['ysellernum'] = $v['ysellernum'];
                $v['t1.pm'] = $v['pm'];
                $v['sellermoney'] = sprintf('%.2f',$v['sellermoney']);
                $v['geshu'] =  $geshu[0]['n'];
                $v['zje'] =  $geshu[0]['znum'];
                $v['zbuy'] = $geshu[0]['zbuy'];
                $v['special_condition'] = $v['account'];
                $v['pn'] = isset($buynum[$v['userid']]) ? $buynum[$v['userid']] : 0;
                $v['t1.flag'] = $fun->getStatus($v['flag'],['普通店铺','<span style="color:red">怀米网店铺</span>','消保店铺']);
                $v['t1.shopzt'] = $fun->getStatus($v['shopzt'],['--','<span style="color:green">正常开店</span>','<span style="color:red">正在审核</span>','<span style="color:red">禁用</span>','<span style="color:red">审核被拒</span>']);
                $v['upaytime'] = $paytime;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    //店铺修改
    public function modi(){

        if($this->request->isPost()){
            $shopParam = $this->request->post('shop/a');
            if(empty($shopParam['shopname'])){
                $this->error('店铺名字不能为空');
            }

            $shopParam['shopzt'] = isset($shopParam['shopzt']) ? $shopParam['shopzt'] : 2;

            $addAccount = false;
            
            $accCount = Db::name('store_account')->where('userid',$shopParam['userid'])->count();
            if($shopParam['shopzt'] == 1 || $shopParam['shopzt'] == 3){
                //修改店铺参数
                $odltype = $this->request->post('oldtype');

                if( $odltype == 0 && $shopParam['shopzt'] == 1 && in_array($shopParam['flag'],[1,2])){
                    $shopParam['cols_param'] = 'txt,dqsj,icptrue,security,icp_org,sogou_pr,zcs'; //zcsj,
                }
                //卖家交易锁
                Fun::ini()->lockKey($shopParam['userid'].'_domain_transaction') || $this->error('系统繁忙,请稍后再试!');
                $this->updateShopDomainStatus($shopParam['shopzt'],$shopParam['userid']);
                Fun::ini()->unlockKey($shopParam['userid'].'_domain_transaction');

                if($shopParam['shopzt'] == 1 && !$accCount){
                    $addAccount = true;
                }

            }
            //拒绝申请
            if($shopParam['flag'] == 2 && $shopParam['shopzt'] == 4){
                //消保扣手续费
                $bao = Db::name('domain_baomoneyrecord')->where(['userid' => $shopParam['userid'],'status' => 0,'type' => 4])->field('id,moneynum,uip')->order('id desc')->find();
                if($bao){
                    //退还保证金并降级为普通店铺
                    Fun::ini()->lockBaoMoney($shopParam['userid']) || $this->error('系统繁忙,请稍后操作!');
                        Db::name('domain_user')->where('id',$shopParam['userid'])->setDec('baomoney1',$bao['moneynum']);
                    Fun::ini()->unlockBaoMoney($shopParam['userid']);
                    Db::name('domain_baomoneyrecord')->where(['id' => $bao['id']])->setField('status',2);
                }
                $shopParam['flag'] = 0;
                $shopParam['shopzt'] = 1;
                $shopParam['deposit'] = 0;
                if(!$accCount){
                    $addAccount = true;
                }

            }

            $redis = new Redis();

            Db::startTrans();

            try{
                //添加店铺账号
                if($addAccount){
                    //写入店铺号账号
                        $lock = $redis->lock('get_shop_account_num',10);
                        if(!$lock){
                            Db::rollback();
                            throw new Exception('系统繁忙,请稍后再试!');
                        }

                        $acc = Db::name('store_account')->where('gain_type',0)->where('remark != "默认开通店铺账号"')->order('account','desc')->value('account');

                        $acc = empty($acc) ? 30010 : $acc;
                        if($acc > 99889){
                            Db::rollback();
                            $redis->unlock('get_shop_account_num');
                            throw new Exception('店铺账号已使用完,请联系管理员！');
                        }

                        $shopParam['account'] = $this->checkShopAccount(($acc+1));

                        Db::name('store_account')->insert([
                            'userid' => $shopParam['userid'],
                            'create_time' => time(),
                            'account' => $shopParam['account'],
                            'is_default' => 1,
                            'remark' => '默认生成店铺号',
                        ]);

                }

                Db::name('storeconfig')->where(['userid'=>$shopParam['userid']])->update($shopParam);

            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();
            $redis->unlock('get_shop_account_num');
            $this->success('修改成功');
        }
        $ids = $this->request->get('ids');
        if(!intval($ids)){
            $this->error('参数有误');
        }
        $data = Db::name('storeconfig')->alias('s')->join('domain_user u','s.userid=u.id')->field('s.userid,s.shopname,s.img,s.notice,s.uqq,u.mot,s.seotit,s.seokey,s.seodes,s.sj,s.pm,s.xinyong,s.flag,s.shopzt,s.shopztsm,s.djl,s.remark')
            ->where('userid',$ids)
            ->find();
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
    /**
     * 禁止状态 记录用户一口价id并下架  启用状态 恢复
     */
    private function updateShopDomainStatus($shopzt,$userid){
        //恢复被禁止店铺时的上架域名id
        $redis = new Redis(['select' => 7]);
        $key = 'shop_user_forbie_'.$userid;
        if($shopzt == 1){//恢复时
            $borbid = $redis->hgetall($key);
            if($borbid){
                Db::name('domain_pro_trade')->whereIn('id',$borbid)->setField('status',1);
                $redis->del($key);
            }
        }else{ //禁止时
            $borbid = Db::name('domain_pro_trade')->where(['userid' => $userid,'status' => 1])->column('id');
            if($borbid){
                Db::name('domain_pro_trade')->whereIn('id',$borbid)->setField('status',2);
                $redis->hMset($key,$borbid);
            }
        }
        return true;
    }

    /**
     * 退保
     */ 
    public function audit($ids){
        if(intval($ids)){
            $shopInfo = Db::name('storeconfig')->field('deposit')->where(['userid' => $ids,'flag' => 2])->find();
            if(empty($shopInfo)){
                $this->error('店铺已退保');
            }

            $bao = Db::name('domain_baomoneyrecord')->where(['userid' => $ids,'status' => 0,'type' => 4])->field('id,moneynum,uip')->order('id desc')->select();

            $baomoney = array_sum(array_column($bao,'moneynum'));
            if($baomoney != $shopInfo['deposit']){
                $this->error('保证金异常');
            }
            // if(empty($bao)){
            //     $this->error('保证金记录状态错误');
            // }else{
                Db::startTrans();
                if($bao){

                    Fun::ini()->lockBaoMoney($ids) || $this->error('系统繁忙,请稍后操作!');
                        Db::name('domain_user')->where('id',$ids)->setDec('baomoney1',$baomoney);
                    Fun::ini()->unlockBaoMoney($ids);
                   
                    Db::name('domain_baomoneyrecord')->whereIn('id',array_column($bao,'id'))->setField('status',2);
                }
                
                Db::name('storeconfig')->where('userid',$ids)->update(['flag' => 0,'cols_param' => '','remark' => '退保,降级为普通店铺！','deposit' => 0]);

                Db::commit();
            // }
            $this->success('操作成功');
        }
        return $this->error('缺少重要参数');
    }
    /**
     * 点击后价格修改ajax
     */
    public function updatePm()
    {
        $data = $this->request->post();
        Db::name('storeconfig')->where(['userid'=>intval($data['id'])])->update(['pm'=>intval($data['val'])]);
        echo $data['val'];
       
    }

    /**
     * 账号管理
     */
    public function account(){

        if($this->request->isAjax()){

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = Db::name('store_account')->alias('a')->join('domain_user u','a.userid=u.id')->join('storeconfig s','a.userid=s.userid')
                    ->where($where)
                    ->count();

            $list = Db::name('store_account')->alias('a')->join('domain_user u','a.userid=u.id')->join('storeconfig s','a.userid=s.userid')
                    ->field('a.id,a.endtime,a.status,a.create_time,a.account,a.is_default,a.gain_type,a.remark,u.uid,s.shopname')
                    ->where($where)
                    ->order($sort,$order)
                    ->limit($offset,$limit)
                    ->select();

            $fun = Fun::ini();

            foreach($list as &$v){

                $v['a.endtime'] = $v['endtime'] ;
                $v['a.status'] = $fun->getStatus($v['status'],['正常','禁用']);
                $v['a.create_time'] = $v['create_time'];
                $v['u.uid'] = $v['uid'];
                $v['is_default'] = $fun->getStatus($v['is_default'],['否','是']);
                $v['gain_type'] = $fun->getStatus($v['gain_type'],['默认开通','合作方']);

                if($v['remark']){
                    $v['remark'] = '<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['remark'].'\')" >查看</span>'; 
                }
            
            }            
            $result = array("total" => $total, "rows" => $list);
            return json($result);

        }
        $this->assign('uid',$this->request->get('u_uid'));
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function accountadd(){
        
        if($this->request->isPost()){
            global $remodi_db;

            $params = $this->request->post('row/a');
            if(empty($params['uid'])){
                $this->error('请填写用户名');
            }

            if(empty($params['account'])){
                $this->error('请填写店铺靓号');
            }

            $params['endtime'] = empty($params['endtime']) ? 0 : strtotime($params['endtime']);

            $time = time();

            if($params['endtime'] && $params['endtime'] <= $time){
                $this->error('结束时间不得小于现在的时间');
            }

            if($params['is_default'] == 1 && $params['status'] == 1){
                $this->error('设置默认店铺号状态不能为禁用');
            }


            $userid = Db::name('domain_user')->alias('u')->join('storeconfig s','u.id=s.userid')->where(['u.uid' => trim($params['uid'])])->value('u.id');

            if(empty($userid)){
                $this->error('用户不存在或者该用户未开通店铺');
            }

            $params['account'] = trim($params['account']);

            $flag = Db::connect($remodi_db)->name('keep_account')->where(['account' => $params['account'],'status' => 0,'type' => 0])->count();
            if(empty($flag)){
                $this->error('靓号不存在或者已被使用!');
            }

            //暂时限制5个
            $accountTotal = Db::name('store_account')->where('userid',$userid)->count();

            if($accountTotal >= 5){
                $this->error('该用户已有5个店铺号,无法添加！');
            }

            //逐步事务
            $a1 = Db::connect($remodi_db)->name('keep_account')->where(['type' => 0,'status' => 0,'account' => $params['account']])->setField('status',1);
            if(empty($a1)){
                $this->error('靓号库修改失败');
            }

            $aid = Db::connect($remodi_db)->name('keep_account_operate_record')->insertGetId([
                'account' => $params['account'],
                'type' => 0,
                'optype' => 0,
                'create_time' => $time,
                'userid' => $userid,
                'end_time' => $params['endtime'],
            ]);

            Db::startTrans();
                try{

                    if($params['is_default'] == 1){
                        Db::name('store_account')->where(['userid' => $userid,'is_default' => 1])->setField('is_default',0);
                        Db::name('storeconfig')->where('userid',$userid)->setField('account',$params['account']);
                    }
                    Db::name('store_account')->insert([
                        'userid' => $userid,
                        'endtime' => $params['endtime'],
                        'create_time' => $time,
                        'account' => $params['account'],
                        'is_default' => $params['is_default'],
                        'gain_type' => 1,
                    ]);

                }catch(Exception $e){

                    Db::rollback();
                    Db::connect($remodi_db)->name('keep_account')->where(['type' => 0,'status' => 1,'account' => $params['account']])->setField('status',0);
                    Db::connect($remodi_db)->name('keep_account_operate_record')->where('id',$aid)->delete();
                    $this->error($e->getMessage());
                }

            Db::commit();

                $this->success('添加成功');


        }

        $this->view->assign('uid',$this->request->get('uid'));
        return $this->view->fetch();
    }

    /**
     * 账号状态修改
     */
    public function accountmodi(){
        
        if($this->request->isAjax()){
            
            global $remodi_db;
            
            $params = $this->request->get();
           
            if(empty($params['id']) || !isset($params['status'])){
                $this->error('缺少重要参数');
            }

            $info = Db::name('store_account')->field('account,userid,is_default')->where(['id' => $params['id'],'status' => $params['status']])->where('gain_type != 0')->find();
            if(empty($info)){
                $this->error('记录不存在或状态已被修改,请刷新页面！');
            }

            if($params['status'] == 0){
                $msg = '禁用';
                $status = 1;
                $type = 1;
            }else{
                $msg = '恢复正常';
                $status = 0;
                $type = 0;
            }

            $remark = isset($params['remark']) ? $params['remark'] : '';
            Db::startTrans();

            if($params['status'] == 0 && $info['is_default'] == 1){

                $old = Db::name('store_account')->where(['userid' => $info['userid'],'gain_type' => 0])->value('account');

                    Db::name('store_account')->where('id',$params['id'])->update(['status' => 1,'is_default' => 0,'remark' => $remark]);
                    Db::name('store_account')->where(['userid' => $info['userid'],'gain_type' => 0])->setField('is_default',1);
                    Db::name('storeconfig')->where('userid',$info['userid'])->setField('account',$old);

            }else{

                Db::name('store_account')->where('id',$params['id'])->update(['status' => $status,'remark' => $remark]);

            }
            //修改关联表
            Db::name('store_relevance')->where(['relevance_account' => $info['account'],'rel_status' => 0])->setField('status',$status);

            Db::commit();



            $uid = Db::name('domain_user')->where('id',$info['userid'])->value('uid');

            $obj = new sendMail();

            $obj->shopAccount($info['userid'],$uid,$status,$info['account'],$remark);
            
            $this->success('店铺号'.$msg.'成功');
        }   

    }


    /**
     * 账号删除
     */
    public function accountdel(){


        if($this->request->isAjax()){

            global $remodi_db;

            $params = $this->request->get();

            $ids = empty($params['id']) ? 0 : intval($params['id']);
            if(empty($ids) || empty($params['remark'])){
                $this->error('缺少重要参数');
            }

            $info = Db::name('store_account')->field('gain_type,userid,is_default,account,endtime')->where('id',$ids)->find();
            
            if(empty($info)){
                $this->error('店铺账号信息不存在,请确认！');
            }

            if($info['gain_type'] === 0){
                $this->error('默认开通账号类型不可删除');
            }

            //跨库数据库事务不能返回 先释放靓号及记录
            $flag = Db::connect($remodi_db)->name('keep_account')->where(['account' => $info['account'],'status' => 1,'type' => 0])->setField('status',0);
            if($flag){

                $aa = Db::connect($remodi_db)->name('keep_account_operate_record')->insert([
                    'account' => $info['account'],
                    'type' => 0,
                    'optype' => 2,
                    'create_time' => time(),
                    'userid' => $info['userid'],
                    'end_time' => $info['endtime'],
                ]);

                if(empty($aa)){
                    Db::connect($remodi_db)->name('keep_account')->where(['account' => $info['account'],'type' => 0])->setField('status',1);
                    $this->error('靓号操作记录异常');
                }

            }else{
                $this->error('靓号释放异常');
            }

            Db::startTrans();
            try{

                Db::name('store_account')->where('id',$ids)->delete();
                //如果为主账号 重新赋值
                if($info['is_default'] == 1){
                    Db::name('store_account')->where(['userid' => $info['userid'],'gain_type' => 0] )->setField('is_default',1);
                    $old = Db::name('store_account')->where(['userid' => $info['userid'],'gain_type' => 0])->value('account');
                    Db::name('storeconfig')->where('userid',$info['userid'])->setField('account',$old);
                }
                //修改关联表
                Db::name('store_relevance')->where(['relevance_account' => $info['account'],'rel_status' => 0])->update(['rel_status' => 1,'remark' => '系统删除店铺号']);

            }catch(Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();


            $uid = Db::name('domain_user')->where('id',$info['userid'])->value('uid');

            $obj = new sendMail();

            $obj->shopAccount($info['userid'],$uid,2,$info['account'],$params['remark']);

            $this->success('删除成功');
        }


    }

    /**
     * 店铺号关联
     */
    public function relevance(){

        if($this->request->isAjax()){

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('store_relevance')->alias('r')->join('domain_user u','r.userid=u.id')->join('domain_user u1','r.relevance_userid=u1.id')
                ->where($where)
                ->count();

            $list = Db::name('store_relevance')->alias('r')->join('domain_user u','r.userid=u.id')->join('domain_user u1','r.relevance_userid=u1.id')
                ->field('relevance_account,status,create_time,rel_status,remark,u.uid,u1.uid as u1id')
                ->where($where)
                ->order($sort,$order)
                ->limit($offset,$limit)
                ->select();

            $fun = Fun::ini();
            foreach($list as &$v){
                $v['r.status'] = $fun->getStatus($v['status'],['正常','禁用']);
                $v['rel_status'] = $fun->getStatus($v['rel_status'],['关联中','已取消']);
                $v['r.create_time'] = $v['create_time'];
                $v['u.uid'] = $v['uid'];
                $v['u1.uid'] = $v['u1id'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);

        }
        return $this->view->fetch();
    }

    /**
     * 检测店铺号是否可用
     */

    private function checkShopAccount($account){

        global $remodi_db;

        $last = intval($account+19);

        //生成20个店铺号进行筛选
        $arr = range($account,$last);

        $goodAccounts = Db::connect($remodi_db)->name('keep_account')->whereIn('account',$arr)->where('type',0)->column('account');
        
        $usable = array_diff($arr,$goodAccounts); 

        if($usable){
            //判断店铺号是否已存在
            $shops = Db::name('storeconfig')->whereIn('account',$usable)->order('account','asc')->column('account');
            $shopuse = array_diff($usable,$shops);

            if($shopuse){
                return current($shopuse);
            }
        }

        return self::checkShopAccount(++$last);


    }

}
