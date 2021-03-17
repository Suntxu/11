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
                    ->field('c.id as cid,c.money,c.final_money,c.bc,c.tit,c.userid,c.paytime,u.uid as uuid,u1.uid as u1id,c.sxf,c.tmoney,c.agent_cost,c.out_time,c.status,c.jm_id,u2.uid as u2id,c.zcs_money')
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

}


