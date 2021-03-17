<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 红包使记录
 *
 * @icon fa fa-user
 */
class Withdraw extends Backend
{

    protected $relationSearch = false;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
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
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('domain_promation_reward_log')->alias('l')->join('domain_user u','l.userid=u.id','left')
                    ->where($where)
                    ->count();
            $list = Db::name('domain_promation_reward_log')->alias('l')->join('domain_user u','l.userid=u.id','left')
                    ->field('l.id,u.uid,l.money,l.status,l.cids,l.ctime')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            foreach($list as $k => $v){
               $list[$k]['on'] = '查看';
               $list[$k]['money'] = $v['money']/100;
               $list[$k]['l.ctime'] = $v['ctime'];
               $list[$k]['status'] = Fun::ini()->getStatus($v['status'],['等待审核','提取成功','提取失败']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign([
            'id' => $this->request->get('id'),
            'status' => $this->request->get('status'),
        ]);
        return $this->view->fetch();
    }
    /**
     * 提现审核
     */
    public function edit($ids=null){
        if($this->request->isAjax()){
            $status = $this->request->post('status');
            $id = $this->request->post('id');
            $au_remark = $this->request->post('au_remark');
            $time = time();
            if($id && $status){
                    $cid = Db::name('domain_promation_reward_log')->field('cids,money,userid,ip,id')->find($id);
                    if( $status == 1){
                        // 如果提现成功
                        Db::startTrans();
                        // 更新佣金流水表
                        $o1 = Db::name('spreader_flow')->where(['type'=>1,'status'=>1,'userid'=>$cid['userid']])->whereIn('flow_id',$cid['id'])->update(['status'=>2,'updatetime'=>$time]);
                        // 扣除未到账奖励
                        $kc = Db::name('domain_promotion')->where(['userid'=>$cid['userid']])->update(['wait_reward'=>-$cid['money'],'extracted_reward'=>$cid['money']]);
                        // 插入用户余额
                        $yy = Db::name('domain_user')->where(['id'=>$cid['userid']])->setInc('money1',($cid['money']/100));
                        // 插入资金明细表
                        $zij = Db::name('domain_user')->field('money1')->where(['id'=>$cid['userid']])->find();
                        $mx = Db::name('flow_record')->insert([
                            'userid'=> $cid['userid'],
                            'product' => 5,
                            'subtype' => 10,
                            'balance' => $zij['money1'],
                            'money'   =>  ($cid['money']/100),
                            'uip'=> $cid['ip'],
                            'sj'=> date('Y-m-d H:i:s'),
                            'uip'=> $cid['ip'],
                            'infoid' => $cid['id'],
                        ]);
                        // 更新域名表
                        if($o1 && $yy && $mx && $kc){
                            Db::commit();
                        }else{
                            // 回滚事务
                            Db::rollback();
                        }
                    }else{
                        Db::name('spreader_flow')->where(['type'=>1,'status'=>1,'userid'=>$cid['userid']])->whereIn('flow_id',$cid['id'])->update(['status'=>0]);
                        // Db::name('domain_car')->where(['status'=>1])->whereIn('id',$cid['cids'])->update(['e_status'=>0]);
                    }
                    Db::name('domain_promation_reward_log')->where(['id'=>$id])->update(['status'=>$status,'au_remark'=>$au_remark,'au_time'=>$time]);
                    $this->success('操作成功');
                }else{
                    $this->error('无效参数');
                }
            }
        $list = Db::name('domain_promation_reward_log')->alias('l')->join('domain_user u','l.userid=u.id','left')
                    ->field('l.id,u.uid,l.money,l.status,l.cids,l.ctime,l.au_remark,l.au_time')
                    ->where(['l.id'=>$ids])->find();
        $list['money'] /= 100;
        $this->view->assign('data',$list);
        return $this->view->fetch();
    }
}

 



