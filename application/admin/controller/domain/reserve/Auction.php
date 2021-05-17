<?php

namespace app\admin\controller\domain\reserve;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 竞拍列表
 *
 * @icon fa fa-user
 */
class Auction extends Backend
{
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_auction_info');
    }
    /**
     * 查看
     */
    public function index(){
        if ($this->request->isAjax()){
            list($where, $sort, $order, $offset, $limit,$group,$special_condition,$spec) = $this->buildparams();
            $time = time();
            $def = '1 = 1';
            if($group == 1){ //未开始
                $def .= ' and i.start_time > '.$time;
            }elseif($group == 2){ //进行中
                $def .= ' and i.start_time < '.$time.' and end_time > '.$time;
            }elseif($group == 3){
                $def .= ' and i.end_time < '.$time;
            }
            if($special_condition){
                $def .= ' and i.hz = "'.ltrim($special_condition,'.').'"';
            }

            if($spec){
                $userdis = Db::name('domain_user_config')->where('ali_rebate_first_b != 0')->column('userid');
                $fl = $spec == 1 ? ' in ' : ' not in ';
                $def .= ' and lx_userid '.$fl.' ('.implode(',',$userdis).') ';
            }

            $total = $this->model->alias('i')->join('domain_user u','i.lx_userid=u.id','left')
                    ->field('count(*) as n,sum(i.cur_money) as c,sum(i.transferprice) as d,sum(i.outer_price) as o')
                    ->where($where)->where($def)
                    ->find();
            $list = $this->model->alias('i')->join('domain_user u','i.lx_userid=u.id','left')
                ->field('u.uid,i.inner,i.api_id,i.tit,i.cur_money,i.start_time,i.end_time,i.money,i.transferprice,i.type,i.status,i.id,i.hz,i.ptime,i.lx_userid,i.outer_price')
                ->where($where)->where($def)->order($sort,$order)
                ->limit($offset,$limit)
                ->select();
            //返利总金额
            $zreta = $this->model->alias('i')->join('domain_user u','i.lx_userid=u.id')->join('domain_order_reserve r','r.auction_id = i.id')->join('flow_record f','i.tit=f.info')
                    ->where(['i.type' => 1,'f.product' => 7,'subtype' => 15])->whereIn('r.pstatus',[1,3])->where($where)->where($def)
                    ->sum('f.money');

            $tnum =  sprintf('%.2f',$total['c'] + $total['d']) - $zreta;
            $currMoney = sprintf('%.2f',$total['c']);
            $outMoney = sprintf('%.2f',$total['o']);
            $fun = Fun::ini();
            $apis = $this->getApis();
            foreach($list as &$v){

                if($v['start_time'] > $time){
                    $v['group'] = '<span style="color:gray">未开始</span>';
                }elseif($v['start_time'] < $time && $v['end_time'] > $time){
                    $v['group'] = '<span style="color:red">进行中</span>';
                }else{
                    $v['group'] = '<span style="color:blue">已结束</span>';
                }

                $v['i.inner'] = $fun->getStatus($v['inner'],['正常竞价','<span style="color: orange;">内部竞价</span>']);

                $v['i.ptime'] = $v['ptime'];
                $v['i.status'] = $fun->getStatus($v['status'],['进行中','<span style="color:yellowgreen;">竞价成功</span>','<span style="color:red;">竞价失败</span>','<span style="color:darkgreen;">交割成功</span>','<span style="color:orange;">内部竞价</span>']);
                $v['i.type'] = $fun->getStatus($v['type'],['预定','预释放']);
                $v['special_condition'] = $v['hz'];
                $v['i.tit'] = $v['tit'];
                if(empty($v['uid'])){
                    $v['uid'] = '外部领先';
                }
                $v['i.api_id'] = empty($apis[$v['api_id']]) ? '-' : $apis[$v['api_id']];
                $v['realitypay'] = $v['transferprice'] + $v['cur_money'];
                $v['spec'] = '';
                $v['reta'] = 0;
                $v['i.outer_price'] = $v['outer_price'];
                if($v['lx_userid'] != -1 && $v['type'] == 1 ){
                    $pstatus = Db::name('domain_order_reserve')->where(['auction_id' => $v['id'],'userid' => $v['lx_userid'] ])->value('pstatus');
                    if(in_array($pstatus,[1,3])){
                        $vmoney = Db::name('flow_record')->where(['product' => 7,'subtype' => 15,'info' => $v['tit'],'userid' => $v['lx_userid'] ])->value('money');
                        $v['realitypay'] = $v['realitypay'] - $vmoney;
                        $v['reta'] = $vmoney;
                    }
                }
                
                $v['tnum'] = $tnum;
                $v['zreta'] = $zreta;
                $v['currMoney'] = $currMoney;
                $v['outMoney'] = $outMoney;
            }
            return json(['total'=>$total['n'],'rows'=>$list]);
        }
        return $this->view->fetch();
    }
}
