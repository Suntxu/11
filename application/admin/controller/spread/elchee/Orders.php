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
class Orders extends Backend
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
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('domain_order')->alias('c')->join('domain_user u','c.userid=u.id')->join('domain_coupon_log pl','c.bc=pl.bc','left')->join('domain_coupon p','pl.cid=p.id','left')->join('spreader_flow f','c.bc=f.infoid')->join('domain_user u1','f.userid=u1.id')
                    ->where($where)->where(['c.status' => 1,'f.yjtype'=> 0,'f.type' => 1])->whereIn('c.sptype',[2,3])
                    ->where('u1.uid','not null')
                    ->count();
          
            $list = Db::name('domain_order')->alias('c')->join('domain_user u','c.userid=u.id')->join('domain_coupon_log pl','c.bc=pl.bc','left')->join('domain_coupon p','pl.cid=p.id','left')->join('spreader_flow f','c.bc=f.infoid')->join('domain_user u1','f.userid=u1.id')
                    ->field('c.id as cid,c.money,c.final_money,c.bc,c.tit,c.userid,c.paytime,u.uid as uuid,p.title,u1.uid as u1id,c.sptype,c.money - c.final_money as coupon_amount,c.sxf,c.tmoney,c.is_sift,c.type,c.agent_cost')
                    ->where($where)->where(['c.status' => 1,'f.yjtype'=> 0,'f.type' => 1])->whereIn('c.sptype',[2,3])
                    ->where('u1.uid','not null')
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(c.money) as n,sum(c.final_money) as s,sum(c.sxf) as f,sum(c.tmoney) as t FROM '.PREFIX.'domain_order  c inner join '.PREFIX.'spreader_flow f on c.bc=f.infoid inner join '.PREFIX.'domain_user u1 on f.userid=u1.id  WHERE u1.uid is not null and f.type = 1 AND c.sptype in (2,3) and f.yjtype=0 and c.status = 1';
            }else{
                $conm = 'SELECT sum(c.money) as n,sum(c.final_money) as s ,sum(c.sxf) as f,sum(c.tmoney) as t FROM '.PREFIX.'domain_order c  inner join '.PREFIX.'spreader_flow f on c.bc=f.infoid inner join '.PREFIX.'domain_user u on c.userid=u.id inner join '.PREFIX.'domain_user u1 on f.userid=u1.id '.$sql.' and f.type = 1 and c.sptype in (2,3) and f.yjtype=0 and c.status = 1 and u1.uid is not null ';// and c.coupon_amount != 0
            }
            $res = Db::query($conm);
            // 实付总金额
            $yh = $res[0]['n']-$res[0]['s'];
            $fun = Fun::ini();
            foreach($list as $k => $v){
               $list[$k]['c.bc'] = $v['bc']; 
               $list[$k]['zje'] = $res[0]['n'];
               $list[$k]['zje1'] = $yh;
               $list[$k]['coupon_amount'] = $v['coupon_amount'];
               $list[$k]['c.money'] = $v['money'];
               // 实付金额
               $list[$k]['sfzje'] = $res[0]['s'];
               $list[$k]['u.uid'] = $v['uuid'];
               // 手续费 佣金
               $list[$k]['zsxf'] = $res[0]['f'];
               $list[$k]['zyj'] = $res[0]['t'];
               $list[$k]['u1.uid'] = $v['u1id'];

               if($v['type'] == 9){
                   $list[$k]['tit'] .= '<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showPack('.$v['cid'].')" >查看更多</span>';
               }
               $list[$k]['c.type'] = $fun->getStatus($v['type'],['正常订单','满减订单','微信活动订单',9=>'打包域名订单']);

               if($v['sptype'] == 2){
                 $list[$k]['c.sptype'] = '怀米大使';
               }else{
                 $list[$k]['c.sptype'] = '分销系统';
                 $list[$k]['final_money'] = $v['final_money'].'<br><span style="color:red;font-size:8px;">卖出'.$v['agent_cost'].'</span>';    
               }
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign(['id'=>str_replace(',','|',$this->request->get('c_bc')),'uid'=>$this->request->get('uid')]);
        return $this->view->fetch();
    }

}


