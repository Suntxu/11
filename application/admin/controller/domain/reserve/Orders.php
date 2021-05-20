<?php

namespace app\admin\controller\domain\reserve;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名预定
 *
 * @icon fa fa-user
 */
class Orders extends Backend
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
            $def = 'r.type = 0 ';
            if($group){
                $def .= ' and i.hz = "'.ltrim($group,'.').'"';
            }
            if($nouid){
                $uids = Fun::ini()->moreRow($nouid);
                $def .= ' and u.uid not in("'.implode('","',$uids).'") ';
            }
            
            //参与人数
            $ple = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user ut','ut.id = r.tuserid','left')
                    ->where($where)->where($def)
                    ->count('distinct userid');

            //统计总金额 /未支付余款        
            $moneys = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user ut','ut.id = r.tuserid','left')
                ->field('sum(r.money) as pay,sum(fmoney) as spay,sum(nowpay) as nopay')
                ->where($where)->where($def)
                ->find();
           
            //得标数
            $bidTotal = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user ut','ut.id = r.tuserid','left')
                ->where($where)->where($def)->where('r.status',7)
                ->count();
            
            //怀米大使数量
            $elcheeTotal = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user ut','ut.id = r.tuserid')
                ->where($where)->where($def)
                ->count('distinct r.tuserid');

            //非怀米大使的数量
            $noElchTotal = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user ut','ut.id = r.tuserid','left')
                ->where($where)->where($def)->where('r.tuserid = 0')
                ->count();
            $total = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user ut','ut.id = r.tuserid','left')
                    ->where($where)->where($def)
                    ->count();        

            $list = $this->model->alias('r')->join('domain_user u','u.id=r.userid')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user ut','ut.id = r.tuserid','left')
                    ->field('r.channel,r.tit,r.time,r.money,r.uip,r.status,r.pstatus,u.uid,r.id,r.into,i.del_time,a.end_time,i.hz,r.nowpay,i.dtype,r.fmoney,r.yj,ut.uid as tuid,a.id as aid,r.api_id')
                    ->where($where)->where($def)->order($sort,$order)
                    ->limit($offset,$limit)
                    ->select();

            $fun = Fun::ini();
            
            $dtype = $fun->getDomainType();
            $apis = $this->getApis();
            foreach($list as $k=>&$v){
                $v['aid'] = empty($v['aid']) ? 0 : $v['aid'];
                $v['r.channel'] = $v['channel'].'号';
                $v['r.yj'] = $v['yj'];
                $v['ut.uid'] = $v['tuid'];
                $v['u.uid'] = $v['uid'];
                $v['pay'] = $moneys['pay'];
                $v['spay'] = $moneys['spay'];
                $v['nopay'] = $moneys['nopay'];
                $v['ple'] = $ple;
                $v['r.fmoney'] = $v['fmoney'];
                $v['r.into'] = $fun->getStatus($v['into'],['正常订单','<span style="color:orange;">闯入订单</span>']);
                $v['r.api_id'] = empty($apis[$v['api_id']]) ? '--' : $apis[$v['api_id']];
                if(empty($v['dtype']) || $v['dtype'] == 'none'){
                    $list[$k]['i.dtype'] = '--';
                }else{
                    $twot = substr($v['dtype'],0,2);
                    if(empty($dtype[$v['dtype'][0]])){
                        $list[$k]['i.dtype'] = empty($dtype[$twot][1][$v['dtype']]) ? '--' : $dtype[$twot][1][$v['dtype']];
                    }else{
                        $list[$k]['i.dtype'] = $dtype[$v['dtype'][0]];
                    }
                }
                $v['group'] = $v['hz'];
                $v['r.tit'] = $v['tit'];
                $v['r.money'] = $v['money'];
                $v['bidTotal'] = $bidTotal;
                $v['elcheeTotal'] = $elcheeTotal;
                $v['noElchTotal'] = $noElchTotal;
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
