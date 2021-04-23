<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use app\library\mailphp\SendM;
use think\Db;
use app\admin\common\Fun;
use app\admin\common\sendMail;
use app\admin\library\Redis;
/**
 * 用户管理
 *
 * @icon fa fa-user
 */
class Setuser extends Backend
{
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this -> model = Db::name('domain_user');
    }
    /**
     * 查看
     */
    public function index($ids='')
    {
        $id = intval($this->request->get('id'));
        if($id){
            // u2.ali_rebate_first_a,u2.ali_rebate_first_b,
            $data = $this->model->where('id',$id)->find();
            $relation = DB::name('domain_promotion_relation')->where('userid',$id)->value('relation_id');
            if($relation){
                $spec = $this->model->where('id',$relation)->value('uid');
                // 获取预释放上级返点信息
                $yushifang = Db::name('domain_reserve_rebate_hmds')->field('zcs,rebate')->where('userid',$id)->select();
                $release=[];
                foreach($yushifang as $v){
                    $release[$v['zcs']] = $v;
                }
            }else{
                $spec = "";
                $release="";
            }

            // 获取客服内容
            $servei = Db::name('user_service')->where('userid',$data['id'])->find();
            if($servei['img']){
                $servei['img'] = '/uploads'.$servei['img'];
            }

            //获取冻结原因
            if($data['zt'] == 3){
                $disInfo = Db::name('user_disable_record')->field('dis_days,remark as dis_remark,dis_time')->where(['type' => 1,'userid' => $data['id']])->order('id','desc')->find();
                $data['dis_days'] = empty($disInfo['dis_days']) ? 0 : $disInfo['dis_days'];
                $data['dis_remark'] = empty($disInfo['dis_remark']) ? '' : $disInfo['dis_remark'];
                $data['dis_aac_flag'] = (!empty($disInfo['dis_time']) && $disInfo['dis_time'] > time()) ? true : false;
            }
            //获取返利配置信息
            $rinfo = Db::name('domain_reserve_rebate_config')->field('zcs,rebate_a,rebate_b')->where('userid',$id)->select();
            $rebate = [];
            foreach($rinfo as $v){
                $rebate[$v['zcs']] = $v;
            }

            $this->view->assign(['data'=>$data,'serv' => $servei,'rebate' => $rebate,'spec'=>$spec,'release'=>$release]);
            return $this->view->fetch();
        }
        return $this -> error('无效参数');
    }
    /**
     * 添加
     */
    public function edit($flag='')
    {
        $id = $this ->request -> post('id',0,'intval');
        if(empty($id)){
            $this->error('缺少重要参数');
        }
        //基本设置
        switch ($flag){
            case 'qjsz':
                $this->setBaseInfo($id);
                break;
            case 'money':
                $this->setMoney($id);
                break;
            case 'socre':
                $this->setIntegral($id);
                break;
            case 'rebates':
                $this->setRebates($id);
                break;
            case 'other':
                $this->setOther($id);
                break;
            case 'release':
                $this->setRelease($id);
                break;
            default:
                $this->error('非法参数');
                break;
        }
    }

    /**
     * 基本信息设置
     */
    public function setBaseInfo($id){

        $params = $this->request->post('row/a');
        $params['special'] = empty($params['special']) ? 0 : $params['special'];
        // 查询是否已经存在数据
        $serc = Db::name('user_service')->where('userid',$id)->field('online')->find();
        $uinfo = $this->model->field('zt,uid,id')->where('id',$id)->find();
        $redis = new Redis();
        if($params['special'] == 1){
            $seri = $this->request->post('serv/a'); //专属客服属性
            if(!preg_match('/^1[23456789]\d{9}$/',$seri['tel'])){
                return $this->error('请输入正确手机号');
            }
            $seri['img'] = str_replace('/uploads','', strstr($seri['img'],'?',true) );
            if($serc){
                Db::name('user_service')->where('userid',$id)->update($seri);
                $redis->Lrem('hm_service_list',0,$id);
                $redis->LPush('hm_service_list',$id);
                $seri['online'] = $serc['online'];
                $redis->hMset('hm_service_list_'.$id,$seri);
            }else{
                $seri['userid'] = $id;
                $seri['createtime'] = time();
                $seri['online'] = 0;
                Db::name('user_service')->insert($seri);
                $redis->LPush('hm_service_list',$id);
                $redis->hMset('hm_service_list_'.$id,$seri);
            }
        }else{
            //删除客服
            if($serc){
                $redis->Lrem('hm_service_list',0,$id);
                $redis->del('hm_service_list_'.$id);
            }
        }
        $dis_aac_flag = $this->request->post('dis_aac_flag',true);
        //禁用/恢复用户
        if(isset($params['zt']) && ($params['zt'] != $uinfo['zt'] || ($params['zt'] == 3 && $uinfo['zt'] == 3) && !$dis_aac_flag)){
            $time = time();
            $dremark = $this->request->post('dis_remark','');
            $disDays = intval($this->request->post('dis_days',0));
            if($params['zt'] == 3){
                if(empty($disDays)){
                    $this->error('请填写禁用天数');
                }
                if(empty($dremark)){
                    $this->error('请填写禁用原因');
                }
                if($uinfo['zt'] == 3){
                    Db::name('user_disable_record')->where(['userid' => $id,'flag' => 0])->where('dis_time','<',time())->setField('flag',1);
                    Db::name('user_disable_record')->insert([
                        'userid' => $id,
                        'type' => 0,
                        'remark' => '',
                        'create_time' => $time,
                        'dis_days' => 0,
                        'admin_id' => $this->auth->id,
                        'dis_time' => $time,
                        'flag' => 1,
                    ]);
                }
                $redis = new Redis();
                $key = $redis->get('login_'.$id);
                $redis->del($key);
            }else if($params['zt'] == 1){ //如果恢复正常 改变标识
                Db::name('user_disable_record')->where('userid',$id)->setField('flag',1);
                $disDays = 0;
                $dremark = '';
            }

            Db::name('user_disable_record')->insert([
                'userid' => $id,
                'type' => ($params['zt'] == 3 ? 1 : 0),
                'remark' => $dremark,
                'create_time' => $time,
                'dis_days' => $disDays,
                'admin_id' => $this->auth->id,
                'dis_time' => strtotime('+'.$disDays.' day'),
                'flag' => ($params['zt'] == 1 ? 1 : 0),
            ]);


        }
        //密码加密
        if(empty($params['pwd'])){
            unset($params['pwd']);
        }else{
            $params['pwd'] = sha1($params['pwd']);
        }
        if(empty($params['zfmm'])){
            unset($params['zfmm']);
        }else{
            $params['zfmm'] = sha1($params['zfmm']);
        }

        //手机认证
        $params['ifmot'] = empty($params['ifmot']) ? 0 : 1;

        $this->model->where(['id'=>$id])-> update($params);
        $this->success('修改成功');
    }

    /**
     * 金额设置
     */
    public function setMoney($id){
        $params = $this->request->post('money/a');
        //金额设置
        if(floatval($params['money']) == 0){
            $this -> error('金钱格式错误');
        }
        // 获取用户的余额
        $usemo = Db::name('domain_user')->field('money1,uid,mot,baomoney1')->where(['id'=>$id])->find();
        $money = sprintf('%.2f',abs($params['money']));
        $t = time();
        $sj = date('Y-m-d H:i:s');
        $uip = $this->request->ip();
        $da = [
            'bh'=>$t,
            'ddbh'=> 'admin_'.$t.rand(1000,9999),
            'userid'=> $id,
            'sj'=> $sj,
            'uip' => $uip,
            'bz' => '人工充值',
            'ifok'=> 1,
            'topspreader' => 0,
            'channel'=>0,
            'admin_id' => $this->auth->id,
            'wxddbh' => $params['wxddbh'],
        ];
        Db::startTrans();
        if($params['op'] == 1){
            //金钱增加
            $tit = empty($params['remark']) ? '帐户金额充值' : $params['remark'];
            Fun::ini()->lockMoney($id) || $this->error('系统繁忙,请稍后操作');
            $da['ddzt'] = '充值成功';
            $da['remark'] = $tit;
            $da['money1'] = $money;
            Db::name('domain_user')->where('id',$id)->setInc('money1',$money);
            $recharge = Db::name('domain_dingdang')->insertGetId($da);
            Db::name('flow_record')->insert([
                'sj'    => $sj,
                'infoid'=> $recharge,
                'product'=> 2,
                'subtype'=> 5,
                'uip'   => $uip,
                'balance' => ($usemo['money1']+$money),
                'money' => $money,
                'userid'=> $id,
            ]);
            Fun::ini()->unlockMoney($id);
        }elseif($params['op'] == 2){
            if(($usemo['money1'] - $usemo['baomoney1']) < $money){
                $this->error('可用金额不足，请确认');
            }
            $tit = empty($params['remark']) ? '帐户金额扣除' : $params['remark'];
            $da['ddzt'] = $tit;
            $da['remark'] = $tit;
            $da['money1']= -$money;
            Fun::ini()->lockMoney($id) || $this->error('系统繁忙,请稍后操作');
            Db::name('domain_user')->where('id',$id)->setInc('money1',-$money);
            $recharge = Db::name('domain_dingdang')->insertGetId($da);
            Db::name('flow_record')->insert([
                'sj'    => $sj,
                'infoid'=> $recharge,
                'product'=> 2,
                'subtype'=> 5,
                'uip'   => $uip,
                'balance' => ($usemo['money1']-$money),
                'money' => -$money,
                'userid'=> $id,
            ]);
            Fun::ini()->unlockMoney($id);
        }elseif($params['op'] == 3){
            //冻结金额
            if(($usemo['money1'] - $usemo['baomoney1']) < $money){
                $this->error('可冻结资金不足，请确认');
            }
            if(empty($params['remark'])){
                $this->error('请填写冻结说明');
            }
            //获取用户信息
            $e = new sendMail();
            $e->freezing($id,$usemo['uid'],$usemo['mot'],$money,($usemo['money1']-$money),$params['remark']);
            Fun::ini()->lockBaoMoney($id) || $this->error('系统繁忙,请稍后操作');
            // 冻结保证金
            Db::name('domain_user')->where('id',$id)->setInc('baomoney1',$money);
            Db::name('domain_baomoneyrecord')->insert([
                'userid' => $id,
                'tit' => $params['remark'],
                'moneynum' => $money,
                'sj' => $sj,
                'uip' => $uip,
            ]);
            Fun::ini()->unlockBaoMoney($id);
        }
        Db::commit();
        return $this->success('修改成功');
    }
    /**
     * 积分设置
     */
    public function setIntegral($id){
        //积分设置
        $score = $this->request->post('score/a');
        if(intval($score['num']) == 0){
            $this -> error('积分数量格式错误');
        }
        $sj = date('Y-m-d H:i:s');
        $uip = $this->request->ip();
        $sm = intval(abs($score['num']));
        $da = [
            'userid'=>$id,
            'sj'=>$sj,
            'uip'=>$uip,
        ];
        Db::startTrans();
        if($score['op'] == 1){
            $da['tit'] = empty($score['remark']) ? '增加积分' : $score['remark'];
            $da['jfnum']= $sm;
            Db::name('domain_user')->where('id',$id)->setInc('jf',$sm);
        }elseif($score['op'] == 2){
            $da['tit'] = empty($score['remark']) ? '积分扣除' : $score['remark'];
            $da['jfnum']= -$sm;
            Db::name('domain_user')->where('id',$id)->setInc('jf',-$sm);
        }
        Db::name('domain_jfrecord')->insert($da);
        Db::commit();
        $this->success('操作成功');
    }

    /**
     *返利设置
     */
    public function setRebates($id){

        $rebates = $this->request->post('rebates/a');

        //获取已设置的信息
        $setInfo = Db::name('domain_reserve_rebate_config')->where('userid',$id)->whereIn('zcs',array_keys($rebates))->column('zcs');

        $update = [];
        foreach($setInfo as $v){
            $update[] = ['zcs' => $v,'rebate_a' => $rebates[$v]['rebate_a'],'rebate_b' => $rebates[$v]['rebate_b']];
            unset($rebates[$v]);
        }
        //获取需要插入的信息
        $insert = [];
        foreach($rebates as $k => $v){
            if($v['rebate_a'] > 0 || $v['rebate_b'] > 0){
                $insert[] = ['userid' => $id,'zcs' => $k,'rebate_a' => $v['rebate_a'],'rebate_b' => $v['rebate_b'] ];
            }
        }

        foreach($update as $v){
            Db::name('domain_reserve_rebate_config')->where(['userid' => $id,'zcs' => $v['zcs']])->update($v);
        }
        if($insert){
            Db::name('domain_reserve_rebate_config')->insertAll($insert);
        }

        $this->success('修改成功');
    }
    /**
     *其他设置
     */
    public function setOther($id){
        $rebates = $this->request->post('other/a');

        $exists = Db::name('domain_user_config')->where('userid',$id)->value('userid');
        if($exists){
            Db::name('domain_user_config')->where('userid',$id)->update($rebates);
        }else{
            if(array_filter($rebates)){
                $rebates['userid'] = $id;
                Db::name('domain_user_config')->insert($rebates);
            }
        }
        $this->success('修改成功');
    }

    /**
     * 预释放上级列表
     */
    public function setRelease($id){
        $release = $this->request->post('release/a');

        if($release['rebate'] < 0 || $release['rebate'] >= 20){
            $this->error('预释放返点比例不能小于0或不超过20');
        }
        $hmds_id = Db::name('domain_reserve_rebate_hmds')->where(['userid'=>$id,'zcs'=> 66])->value('id');
        if($hmds_id){
            DB::name('domain_reserve_rebate_hmds')->where('id',$hmds_id)->setField('rebate',$release['rebate']);
            $this->success('设置成功');
        }else{
            DB::name('domain_reserve_rebate_hmds')->insert(['userid' => $id,'zcs' => 66,'rebate' => $release['rebate']]);
            $this->success('设置成功');
        }
    }

}
