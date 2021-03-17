<?php

namespace app\admin\controller\spread;

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
                    ->field('l.id,u.uid,l.money,l.status,l.cids,l.ctime,l.source')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $fun = Fun::ini();
            foreach($list as $k => $v){
               $list[$k]['on'] = '查看';
               $list[$k]['money'] = $v['money']/100;
               $list[$k]['l.ctime'] = $v['ctime'];
               $list[$k]['status'] = $fun->getStatus($v['status'],['等待审核','提取成功','提取失败']);
               $list[$k]['l.source'] = $fun->getStatus($v['source'],['怀米网','分销系统']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign([
            'id' => $this->request->get('id'),
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
                    Db::startTrans();
                    try{
                        if( $status == 1){
                            // 更新佣金流水表
                            Db::name('spreader_flow')->where(['status'=>1,'userid'=>$cid['userid']])->whereIn('type',[1,2])->whereIn('flow_id',$cid['id'])->update(['status'=>2,'updatetime'=>$time]);
                            // 扣除未到账奖励
                            Db::name('domain_promotion')->where(['userid'=>$cid['userid']])->update(['wait_reward'=>-$cid['money'],'extracted_reward'=>$cid['money']]);
                            // 插入用户余额
                            Db::name('domain_user')->where(['id'=>$cid['userid']])->setInc('money1',($cid['money']/100));
                            // 插入资金明细表
                            $umoney = Db::name('domain_user')->where(['id'=>$cid['userid']])->value('money1');
                            Db::name('flow_record')->insert([
                                'userid'=> $cid['userid'],
                                'product' => 5,
                                'subtype' => 10,
                                'balance' => $umoney,
                                'money'   =>  ($cid['money']/100),
                                'uip'=> $cid['ip'],
                                'sj'=> date('Y-m-d H:i:s'),
                                'uip'=> $cid['ip'],
                                'infoid' => $cid['id'],
                            ]);
                        }else{
                            Db::name('spreader_flow')->where(['status'=>1,'userid'=>$cid['userid']])->whereIn('type',[1,2])->whereIn('flow_id',$cid['id'])->update(['status'=>0]);
                        }
                        Db::name('domain_promation_reward_log')->where(['id'=>$id])->update(['status'=>$status,'au_remark'=>$au_remark,'au_time'=>$time,'admin_id' => $this->auth->id ]); 
                        Db::commit();
                    }catch(\Exception $e){
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
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

 



