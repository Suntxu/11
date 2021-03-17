<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\common\sendMail;
/**
 * 提现
 * @icon fa fa-user
 */
class Tx extends Backend
{
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */

    public function _initialize()
    {
        parent::_initialize();
        $this->model= Db::name('domain_tixian');
    }
    
    /**
     * 查看 
     */
    public function index($ids = '',$status='')
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('t')->join('domain_user u','t.userid=u.id')
                        ->where($where)->count();
            
            $list = $this->model->alias('t')->join('domain_user u','t.userid=u.id')
                        ->field('u.id as userid,u.uid,t.money1,t.sj,t.txyh,t.zt,t.sm,t.id,t.type,t.sm')
                        ->where($where)->order($sort,$order)->limit($offset, $limit)
                        ->select();

            $userids = array_column($list,'userid');

            $promise = array_unique(Db::name('tixian_pledge')->whereIn('userid',$userids)->column('userid'));
            
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_tixian where zt = 1';
            }else{
                $conm = 'SELECT sum(t.money1) as n FROM '.PREFIX.'domain_tixian as t left join '.PREFIX.'domain_user as u on t.userid=u.id '.$sql;
            }
            $res = Db::query($conm);
            $fun = Fun::ini();
            foreach($list as  &$v){
                $v['t.type'] = $fun->getStatus($v['type'],['普通提现','<span style="color:red;">注销提现</span>']);
                $v['u.uid'] = $v['uid'];
                $v['t.sj'] = $v['sj'];
                $v['t.money1'] = sprintf('%.2f',$v['money1']);
                $v['t.txyh'] = $v['txyh'];
                $v['t.zt'] = $fun->getStatus($v['zt'],["--","<span style='color:blue'>提现成功</span>","<span style='color:gray'>用户已经撤销提现</span>","<span style='color:red'>提现失败".$v['sm']."</span>","<span style='color:green'>等待受理</span>","<span style='color:orange;'>提现审核中</span>"]);
                $v['zje'] = $res[0]['n'];

                if(in_array($v['userid'],$promise) && $v['zt'] == 5){
                    $v['op'] = '<a href="/admin/vipmanage/recode/txpromise?uid='.$v['uid'].'" class="dialogit" title="提现承诺信息">查看</a>';
                }else{
                    $v['op'] = '--';
                }

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign([
            'status' => $status,
            'ids'   => $ids,
        ]);
        return $this->view->fetch();
    }
    // 详情
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){

            $this->request->filter(['strip_tags']);
            $params = $this->request->post("row/a");
            if ($params){
                $row = $this->model->alias('t')->join('domain_user u','t.userid=u.id','left')->field('t.money1,t.zt,t.id,t.txzh,t.userid,t.resultmoney,u.uid,t.sj,t.type,t.promise_type')->where(['t.id'=>$params['id']])->whereIn('t.zt',[4,5])->find();
                if(empty($row)){
                    $this->success('记录已审核!');
                }
                
                //获取承诺类型
                $promise_type = $this->request->post('promise_type/a','');

                if((empty($promise_type) || $promise_type[0] === '')){

                    if(empty($params['zt'])){
                        $this->error('请先提现修改状态');
                    }

                    //审核中状态只是防止用户撤销 不做记录 只修改状态
                    if($params['zt'] == 5){
                        $fsm = empty($params['sm']) ? '信息审核中' : $params['sm'];
                        $this->model->where('id',$params['id'])->update(['zt' => 5,'sm' => $fsm]);
                        $this->success('操作成功');
                    }   

                }
                // 发送通知
                $e = new sendMail();
                //承诺书
                if(isset($promise_type[0]) && $promise_type[0] !== ''){
                    
                    if($row['promise_type'] !== ''){
                        $promise = array_merge(explode(',',$row['promise_type']),$promise_type);
                    }else{
                        $promise = $promise_type;
                    }
                    $re = $this->getPromiseInfo($promise);
                    $this->model->where('id',$params['id'])->update([
                        'promise_type' => implode(',',$promise),
                        'sm' => empty($params['sm']) ? $re : $params['sm'],
                    ]);
                                        
                    //发送邮件
                    $e->withdrawPromise($row['userid'],$row['uid'],$row['txzh'],1,$re);
                    $this->success('操作成功,请等待用户提交承诺信息!');
                }

                $sj = time();

                if(empty($params['sm'])){
                    if($params['zt'] == 3){
                        $params['sm'] = '提现失败';
                    }else{
                        $params['sm'] = '提现成功';
                    }
                }

                if($row['promise_type'] !== ''){
                    if(3==$params['zt']){
                        Db::name('tixian_pledge')->where('tid',$params['id'])->delete();
                    }else{
                        $acount = Db::name('tixian_pledge')->where(['tid' => $params['id'],'status' => 1])->count();
                        if($acount != count(explode(',',$row['promise_type']))){
                            $this->error('该记录下还有未审核完成的承诺信息');
                        }
                    }

                }

                Db::startTrans();
                if(3==$params['zt']){
                    if($row['type'] == 1){ //注销提现
                        Db::name('domain_user')->where('id',$row['userid'])->setField('zt',1);
                        Db::name('user_cancel')->where(['userid' => $row['userid'],'status' => 0])->update([
                            'operator_id' => $this->auth->id,
                            'status' => 1,
                            'endtime' => $sj,
                            'msg' => $params['sm'],
                        ]);
                    }

                    Db::name('domain_baomoneyrecord')->where(['infoid' => $row['id'],'type'=>2])->update(['otime' => $sj,'status' => 2,'sremark' => '提现审核失败，已还原']);
                    
                    if(!Fun::ini()->lockBaoMoney($row['userid'])){
                        Db::rollback();
                        $this->error('用户余额被锁定,请稍后操作!');
                    }
                    Db::name('domain_user')->where('id',$row['userid'])->setDec('baomoney1',$row['money1']);
                    Fun::ini()->unlockBaoMoney($row['userid']);

                }elseif(1 == $params['zt']){
                    Db::name('domain_baomoneyrecord')->where(['infoid' => $row['id'],'type'=>2])->update(['otime' => $sj,'status' => 1,'sremark' => '提现审核成功，已扣除']);

                    if(!Fun::ini()->lockFreezing($row['userid'])){
                        Db::rollback();
                        $this->error('用户保证金被锁定,请稍后操作!');
                    }
                    Db::execute('update '.PREFIX.'domain_user set baomoney1 = baomoney1 -'.$row['money1'].',money1 = money1 -'.$row['money1'].' where id = '.$row['userid']);
                    Fun::ini()->unlockFreezing($row['userid']);

                    // 获取用户余额和ip
                    $userInfo = Db::name('domain_baomoneyrecord')->field('uip,sj')->where(['infoid'=>$row['id'],'type'=>2])->find();
                    $umoney = Db::name('domain_user')->where('id',$row['userid'])->value('money1');
                    Db::name('flow_record')->insert([
                            'sj'    => date('Y-m-d H:i:s'),
                            'infoid'=> $row['id'],
                            'product'=> 4,
                            'subtype'=> 8,
                            'uip'   => $userInfo['uip'] ? $userInfo['uip'] : '127.0.0.1',
                            'balance' => $umoney,
                            'money' => -$row['money1'],
                            'userid'=> $row['userid'],
                        ]);
                }
                
                
                if(3==$params['zt']){
                    if($row['type'] == 1){
                        $e->cancelNotice($row['userid'],$row['uid'],1,$params['sm']);
                    }
                    $e->withdraw($row['userid'],$row['uid'] ,$row['money1'],$row['resultmoney'],'error',$params['sm']);
                }else{
                    $e->withdraw($row['userid'],$row['uid'],$row['money1'],$row['resultmoney']);
                }  
                //删除手续费
                unset($params['sxf']);
                //增加管理员操作记录
                $params['admin_id'] = $this->auth->id;
                $this->model->update($params);
                Db::commit();
                $this->success('修改成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->alias('t')->join('domain_user u','t.userid=u.id','left')->join('storeconfig s','u.id=s.userid','left')->field('t.*,u.uid,u.id as userid,u.nc as nickname,s.shopzt')
        ->where(['t.id' => $ids])
        ->find();

        $data['success_promises'] = Db::name('tixian_pledge')->where(['userid' => $data['userid'],'status' => 1])->count();

        if($data['promise_type'] !== ''){
            $data['promise_type'] = explode(',',$data['promise_type']);
        }else{
            $data['promise_type'] = [];
        }
        $data['status'] = Fun::ini()->getStatus($data['zt'],["--","<span style='color:blue'>提现成功</span>","<span style='color:gray'>用户已经撤销提现</span>","<span style='color:red'>提现失败".$data['sm']."</span>","<span style='color:green'>等待受理</span>","<span style='color:orange;'>提现审核中</span>"]);
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }


    /**
     * 获取补充信息类型
     */
     private function getPromiseInfo($promise_type){

        $re = '';
        foreach($promise_type as $v){
            $re .= Fun::ini()->getStatus($v,['身份证正面照片','身份证反面照片','手持身份证正面照片','承诺书照片','企业营业执照照片']).',';
        }

        return rtrim($re,',');

    }



}
