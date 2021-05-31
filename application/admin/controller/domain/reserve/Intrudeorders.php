<?php

namespace app\admin\controller\domain\reserve;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 闯入订单
 *
 * @icon fa fa-user
 */
class Intrudeorders extends Backend
{
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_order_reserve');
    }
    /**
     * 查看
     */
    public function index(){
        if ($this->request->isAjax()){
            list($where, $sort, $order, $offset, $limit,$group,$nouid) = $this->buildparams();
            $def = 'r.into = 1 ';
            if($group){
                $def .= ' and i.hz = "'.ltrim($group,'.').'"';
            }
            if($nouid){
                $uids = Fun::ini()->moreRow($nouid);
                $def .= ' and u.uid not in("'.implode('","',$uids).'") ';
            }
            
            //参与人数
            $ple = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')
                    ->where($where)->where($def)
                    ->count('distinct userid');

            //统计总金额 /未支付余款        
            $moneys = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')
                ->field('sum(r.money) as pay,sum(fmoney) as spay,sum(nowpay) as nopay')
                ->where($where)->where($def)
                ->find();
           
            $total = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')
                    ->where($where)->where($def)
                    ->count();        

            $list = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')
                    ->field('r.tit,r.time,r.money,r.uip,r.status,r.pstatus,u.uid,r.id,i.del_time,a.end_time,i.hz,r.nowpay,r.fmoney,r.yj,a.id as aid,r.api_id,r.type')
                    ->where($where)->where($def)->order($sort,$order)
                    ->limit($offset,$limit)
                    ->select();

            $fun = Fun::ini();
            
            $apis = $this->getApis();
            foreach($list as $k=>&$v){
                $v['aid'] = empty($v['aid']) ? 0 : $v['aid'];
                $v['r.yj'] = $v['yj'];
                $v['u.uid'] = $v['uid'];
                $v['pay'] = $moneys['pay'];
                $v['spay'] = $moneys['spay'];
                $v['nopay'] = $moneys['nopay'];
                $v['ple'] = $ple;
                $v['r.fmoney'] = $v['fmoney'];
                $v['r.api_id'] = empty($apis[$v['api_id']]) ? '--' : $apis[$v['api_id']];
                $v['group'] = $v['hz'];
                $v['r.tit'] = $v['tit'];
                $v['r.money'] = $v['money'];
                $v['r.type'] = $fun->getStatus($v['type'],['<span style="color: red">预定</span>','<span style="color: orangered">预释放</span>']);
                if($v['status'] == 7){
                    $v['r.pstatus'] = $fun->getStatus($v['pstatus'],['<span style="color: red">未支付</span>','<span style="color: orangered">未交割</span>','<span style="color: orange">交割失败</span>','<span style="color: green">已交割</span>','<span style="color: gray">违约</span>']);
                }else{
                    $v['r.pstatus'] = '<span style="color: yellowgreen">未得标</span>';
                }
                if($v['status'] == 7){
                    $v['r.pstatus'] = $fun->getStatus($v['pstatus'],['<span style="color: red">未支付</span>','<span style="color: orangered">未交割</span>','<span style="color: orange">交割失败</span>','<span style="color: green">已交割</span>','<span style="color: gray">违约</span>']);
                }elseif($v['status'] == 0  || $v['status'] == 9){
                    $v['r.pstatus'] = '<span style="color: orange">未开始</span>';
                }else{
                    $v['r.pstatus'] = '<span style="color: darkorange">未得标</span>';
                }
                $v['i.del_time'] = $v['del_time'];
                $v['r.status'] = $fun->getStatus($v['status'],['进行中','<span style="color:green">已预定</span>','<span style="color:pink">竞价中</span>','<span style="color:red">预定失败</span>','--','<span style="color:orange">批量失败进行中</span>','<span style="color:yellowgreen">批量成功进行中</span>','<span style="color:green">得标</span>','<span style="color:yellowgreen">未得标</span>','<span style="color:gray">已提交</span>','<span style="color:darkred">外部得标</span>']);
                $v['r.time'] = $v['time'];
                $v['special_condition'] = '';

            }
            return json(['total'=>$total,'rows'=>$list]);
        }
        return $this->view->fetch();
    }
}
