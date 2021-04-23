<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 用户管理
 *
 * @icon fa fa-user
 */
class Users extends Backend
{
    protected $noNeedRight = ['getUserName'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_user');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit,$group,$special_condition,$spec,$uid) = $this->buildparams();
            $def = ' 1 = 1 ';
            if($uid){
                $def .= ' and (u.uid like "%'.str_replace('@','~',trim($uid)).'%"  or u.uid like "%'.trim($uid).'%" )';
            }
            //获取自己的用户
            $marketings = \think\Config::get('self_marketing');
            $ld = $this->model->whereIn('uid',array_keys($marketings))->column('id');

            if($spec == 1){
                $def .= ' and p.relation_id in ('.implode(',', $ld).') ';
            }elseif($spec === 0){
                $def .= ' and p.relation_id not in ('.implode(',', $ld).') ';
            }

            //统计排序 使用临时表特别慢 增加响应时间
            if($sort == 'xflow' || $sort == 'zflow'){
                set_time_limit(240);
            }

            //身份证状态搜索
            if($special_condition !== ''){
                //未认证  不在认证表里面的
                if($special_condition == -1 ){

                    $ruserids = Db::name('user_renzheng')->column('userid');
                    $def .= ' and u.id not in ('.implode(',',array_unique($ruserids)).') ';

                }else{

                    $arr = Db::name('user_renzheng')->field('userid,status')->select();
                    $uarr = [];
                    foreach($arr as $k => $v){
                        $uarr[$v['status']][] = $v['userid'];
                    }
                    $barr = isset($uarr[0]) ? $uarr[0] : []; //审核中
                    $yarr = isset($uarr[2]) ? $uarr[2] : []; //审核成功
                    $earr = isset($uarr[1]) ? $uarr[1] : []; //审核失败
                    $darr = isset($uarr[9]) ? $uarr[9] : []; //已删除

                    if($special_condition == 2){
                        $sarr = isset($uarr[2]) ? $uarr[2] : [];
                    }elseif($special_condition == 0){
                        $sarr = array_diff($barr,$yarr);
                    }elseif($special_condition == 1){
                        $sarr = array_diff($earr,$yarr,$barr);
                    }else{
                        $sarr = array_diff($darr,$barr,$yarr,$earr);
                    }
                    if($sarr){
                        $def .= ' and u.id  in ('.implode(',',array_unique($sarr)).') ';
                    }else{
                        $def .= ' and u.id  in (0) ';
                    }
                }
            }


            $total = $this->model->alias('u')->join('storeconfig s','u.id = s.userid','left')->join('domain_promotion_relation p','p.userid=u.id ','left')
                ->where($where)->where($def)
                ->count();

            //统计每个用户的总流水
            $to = ',( select sum(money) from '.PREFIX.'flow_record where userid = u.id ) as zflow,( select abs(sum(money)) from '.PREFIX.'flow_record where userid = u.id and money < 0 ) as xflow';

            $list = $this->model->alias('u')->join('storeconfig s','u.id = s.userid','left')->join('domain_promotion_relation p','p.userid=u.id ','left')
                ->where($where)->where($def)
                ->field('u.id,u.qh,u.uqq,u.uid,u.uip,u.sj,u.jf,u.money1,u.zt,u.mot,u.baomoney1,u.special,s.flag,p.relation_id as puserid'.$to)
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            foreach($list as $k => &$v){
                if($v['flag'] === null){
                    $v['s.flag'] = '<span style="color:gray">未开店</span>';
                }else{
                    $v['s.flag'] = $fun->getStatus($v['flag'],['普通店铺','<span style="color:red">怀米网店铺</span>','消保店铺']);
                }
                if(in_array($v['puserid'],$ld)){
                    $v['spec'] = Db::name('domain_user')->where('id',$v['puserid'])->value('uid');
                }else{
                    $v['spec'] = '否';
                }

                $v['u.uqq'] = $v['uqq'];
                $v['u.money1'] = sprintf('%.2f',$v['money1']);
                $v['u.mot'] = $v['mot'];
                $v['u.sj'] = $v['sj'];
                $v['u.uip'] = $v['uip'];
                if(strpos($v['uid'],'~')){
                    $uids = explode('_',$v['uid']);
                    $v['special_status'] = isset($uids[1]) ? str_replace('~','@',$uids[1]) : '--';
                }else{
                    $v['special_status'] = $v['uid'];
                }

                $sfzsm = Db::name('user_renzheng')->where('userid = '.$v['id'])->column('status');
                if(in_array(2,$sfzsm)){
                    $v['special_condition'] = '通过认证';
                }elseif(in_array(0,$sfzsm)){
                    $v['special_condition'] = '审核中';
                }elseif(in_array(1,$sfzsm)){
                    $v['special_condition'] = '认证失败';
                }elseif(in_array(9,$sfzsm)){
                    $v['special_condition'] = '已删除';
                }else{
                    $v['special_condition'] = '未认证';
                }
                $v['kyye'] = sprintf('%.2f',($v['money1'] - $v['baomoney1']));
                $v['u.id'] = $v['id'];;
                $v['u.special'] = $fun->getStatus($v['special'],['<span style="color:gray">普通</span>','<span style="color:red">专属客服</span>']);
                $v['u.zt'] = $fun->getStatus($v['zt'],['--','正常使用','邮箱未激活','禁用','安全码错误过多','<span style="color: orange;">注销审核中</span>','<span style="color:red;">已注销</span>']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
        if($ids){
            $this->model->whereIn('id',$ids)->delete();
            $this->success('删除成功');
        }else{
            $this->error('缺少重要参数');
        }
    }
    /**
     * 跳转到后台
     */
    public function Jump($ids=''){
        if($ids){
            $flag = $this->request->get('flag','');
            $sj = time();
            // 新用户中心
            if($flag == 'new'){
                $token = 'fdsafHHf~`HMjJ^FF';
                $rnd = md5('time='.$sj.'&uid='.$ids.$token);
            }else{
                $aa = '1e4343a123434fds1fdaf4fwafd**7#!~';
                $rnd = Fun::ini()->rands();
                $token = md5($aa.$rnd);
                $this->model->where(['id'=>$ids])->update(['webtoken'=>$token,'totime'=>$sj,'weburl'=>'/user/']);
            }
            return json(['code'=>0,'token'=>$rnd,'uid'=>$ids,'time'=>$sj,'admin_id' => $this->auth->id,'weburl'=>WEBURL]);
        }else{
            return json(['code'=>1,'msg'=>'参数错误']);
        }
    }

    /**
     * 解除冻结
     */
    public function Unfreeze(){

        if($this->request->isAjax()){
            $id = $this->request->get('userid');
            $userid = $this->model->where(['id' => intval($id),'zt' => 4])->value('id');
            if(!$userid){
                $this->error('用户状态错误');
            }
            $this->model->where('id',$userid)->setField('zt',1);
            $this->success('操作成功');
        }

    }

    /**
     * 暂时解除异地登录限制
     */
    public function relieveRemote(){

        if($this->request->isAjax()){

            $id = $this->request->get('userid','','intval');
            if(empty($id)){
                $this->error('缺少重要参数');
            }

            $uid = Db::name('domain_user')->where('id',$id)->value('uid');

            if(empty($uid)){
                $this->error('用户不存在');
            }


            $redis = new Redis();
            $redis->set('not_verify_login_'.$id,1,300);

            $msg = '用户:'.$uid.' 解除5分钟异地限制';

            //插入操作记录
            Db::name('domain_operate_record')->insert(['create_time'=>time(),'type' => 9,'tit'=>'解除异地限制','operator_id'=>$this->auth->id,'value' => $msg]);

            $this->success('操作成功');

        }
    }

    /**
     * 快速修改用户手机号
     */
    public function modiMot(){

        if($this->request->isAjax()){
            $params = $this->request->post();
            if(empty($params['userid']) || empty($params['mot'])){
                $this->error('缺少重要参数');
            }
            if(!preg_match('/^1[23456789]\d{9}$/',$params['mot'])){
                $this->error('请填写正确手机号');
            }
            Db::name('domain_user')->where('id',$params['userid'])->update(['mot'=>$params['mot'],'qh'=>$params['qh']]);
            $this->success('操作成功');

        }

    }

    /**
     * 根据uid获取userid
     */
    public function getUserName(){
        if($this->request->isAjax()){
            $userid = $this->request->post('userid',0);
            $uid = $this->model->where('id',$userid)->value('uid');
            return ['code' => 0,'msg' => $uid];
        }
    }

}
