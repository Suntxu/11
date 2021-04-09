<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 订单记录  外部分销订单
 *
 * @icon fa fa-user
 */
class Ordersagentout extends Backend
{

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
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('domain_order_fx')->alias('c')->join('domain_user u','c.userid=u.id')->join('domain_user u2','c.hm_id=u2.id','left')
                    ->join('domain_user u1','c.topid=u1.id','left')
                    ->where($where)
                    ->count();

            $list = Db::name('domain_order_fx')->alias('c')->join('domain_user u','c.userid=u.id')->join('domain_user u2','c.hm_id=u2.id','left')
                    ->join('domain_user u1','c.topid=u1.id','left')
                    ->field('c.id as cid,c.money,c.final_money,c.bc,c.tit,c.userid,c.paytime,u.uid as uuid,u1.uid as u1id,c.sxf,c.tmoney,c.agent_cost,c.out_time,c.status,c.jm_id,u2.uid as u2id,c.zcs_money,c.rebate_type')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(c.money) as n,sum(c.agent_cost) as s,sum(c.sxf) as f,sum(c.tmoney) as t,sum(c.zcs_money) as z FROM '.PREFIX.'domain_order_fx c inner join '.PREFIX.'domain_user u on c.userid=u.id ';
            }else{
              $conm = 'SELECT sum(c.money) as n,sum(c.agent_cost) as s,sum(c.sxf) as f,sum(c.tmoney) as t,sum(c.zcs_money) as z FROM '.PREFIX.'domain_order_fx c inner join '.PREFIX.'domain_user u on c.userid=u.id left join '.PREFIX.'domain_user u2 on c.hm_id=u2.id left join '.PREFIX.'domain_user u1 on c.topid=u1.id '.$sql;
            }

            $res = Db::query($conm);
            // 实付总金额
            $fun = Fun::ini();
            foreach($list as $k => $v){
               $list[$k]['c.bc'] = $v['bc']; 
               $list[$k]['zje'] = $res[0]['n'];
               $list[$k]['c.money'] = $v['money'];
               $list[$k]['c.status'] = $fun->getStatus($v['status'],['<span style="color:red;">未付款</span>','<span style="color:green;">已付款</span>','<span style="color:orange;">待处理</span>',9 => '<span style="color:gray;">已取消</span>']);
               
               $list[$k]['c.rebate_type'] = $fun->getStatus($v['rebate_type'],['<span style="color:gray;">未返款</span>','<span style="color:red;">已通知</span>','<span style="color:orange;">已返款</span>']);

               // 实付金额
               $list[$k]['sfzje'] = $res[0]['s'];
               $list[$k]['u.uid'] = $v['uuid'];
               // 手续费 佣金
               $list[$k]['zsxf'] = $res[0]['f'];
               $list[$k]['zcsm'] = $res[0]['z'];

               //注册商总金额
               $list[$k]['zyj'] = $res[0]['t'];

               $list[$k]['u1.uid'] = $v['u1id'];
               $list[$k]['u2.uid'] = $v['u2id'];
               // $list[$k]['final_money'] = $v['final_money'].'<br><span style="color:red;font-size:8px;">卖出'.$v['agent_cost'].'</span>';
               $list[$k]['final_money'] = '<span style="color:orange;font-size:8px;">成本:'.$v['agent_cost'].'</span><br><span style="color:red;font-size:8px;">额外:'.($v['final_money'] - $v['agent_cost']).'</span>';    
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    public function recordsales()
    {
       if ($this->request->isAjax())
        {
            global $remodi_db;

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $db=Db::connect($remodi_db);

            $total=$db->name('domain_out_trade_history')
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list=$db->name('domain_out_trade_history')
                     ->where($where)
                     ->order($sort, $order)
                     ->limit($offset, $limit)
                     ->select();

            $fun = Fun::ini();
            foreach ($list as $k => $v) {
               $list[$k]['wxjc'] = $fun->getStatus($v['wxjc'],['<span style="color:green;">未拦截</span>','<span style="color:red;">已拦截</span>','<span style="color:green;">未拦截</span>','<span style="color:red;">已拦截</span>']);
               $list[$k]['qqjc'] = $fun->getStatus($v['qqjc'],['<span style="color:green;">绿色认证</span>','<span style="color:green;">未拦截</span>','<span style="color:red;">已拦截</span>']);
               $list[$k]['bdrz'] = $fun->getStatus($v['bdrz'],['<span style="color:gray;">未查</span>','<span style="color:gray;">认证</span>','<span style="color:gray;">未认证</span>']);
               $list[$k]['bdjc'] = $fun->getStatus($v['bdjc'],['<span style="color:gray;">未检测</span>','<span style="color:gray;">未知</span>','<span style="color:green;">安全</span>','<span style="color:red;">危险</span>']);
               $list[$k]['qiang'] = $fun->getStatus($v['qiang'],['<span style="color:gray;">未查</span>','<span style="color:green;">正常</span>','<span style="color:orange;">污染</span>','<span style="color:red;">被抢</span>']);
               //字符串截取
               if(mb_strlen($list[$k]['txt']) > 4){
                    $list[$k]['txt'] = $fun->returntitdian($v['txt'],4).' <span onclick="showRemark(\''.$v['txt'].'\')" style="color:#52a8f1;cursor: pointer;">查看</span>';
                }
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        return $this->view->fetch();
    }


    /**
     * 更改状态
     */
    public function upmodi(){

      if($this->request->isAjax()){

        $params = $this->request->get();

        $id = empty($params['id']) ? 0 : intval($params['id']);

        $status = empty($params['status']) ? 0 : intval($params['status']);

        if(empty($id)){
          $this->error('缺少重要参数');
        }

        if(!in_array($status,[1,2])){
          $this->error('状态参数不在可选范围内');
        }

        $where = ['id' => $id,'status' => 1];

        $where['rebate_type'] = $status == 1 ? 0 : 1;

        $flag = Db::name('domain_order_fx')->where($where)->count();
        
        if(empty($flag)){
          $this->error('该记录不存在或状态已发生改变,请刷新页面！');
        }

        Db::name('domain_order_fx')->where('id',$id)->setField('rebate_type',$status);

        $this->success('操作完成');

      }
    }



}


