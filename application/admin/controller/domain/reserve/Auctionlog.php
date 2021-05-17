<?php

namespace app\admin\controller\domain\reserve;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 竞拍记录
 *
 * @icon fa fa-user
 */
class Auctionlog extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_auction_record');
    }

    /**
     * 查看
     */
    public function index(){
        if ($this->request->isAjax()){
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $time = time();
            $def = '';
            if($group == 1){ //未开始
                $def = '  i.start_time > '.$time;
            }elseif($group == 2){ //进行中
                $def = 'i.start_time < '.$time.' and end_time > '.$time;
            }elseif($group == 3){
                $def = 'i.end_time < '.$time;
            }
            $total = $this->model->alias('r')->join('domain_auction_info i','r.auction_id=i.id','left')->join('domain_user u','r.userid=u.id','left')
                ->where($where)->where($def)
                ->count();
            $list = $this->model->alias('r')->join('domain_auction_info i','r.auction_id=i.id','left')->join('domain_user u','r.userid=u.id','left')
                ->field('i.tit,r.money,r.time,u.uid,r.res_money,i.start_time,i.end_time,i.status,r.otype') //i.money as imoney,
                ->where($where)->where($def)->order($sort,$order)
                ->limit($offset,$limit)
                ->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                if(empty($v['uid'])){
                    $v['uid'] = '外部领先';
                }
                if($v['start_time'] > $time){
                    $v['group'] = '<span style="color:gray">未开始</span>';
                }elseif($v['start_time'] < $time && $v['end_time'] > $time){
                    $v['group'] = '<span style="color:red">进行中</span>';
                }else{
                    $v['group'] = '<span style="color:blue">已结束</span>';
                }
                $v['i.status'] = $fun->getStatus($v['status'],['进行中','<span style="color:yellowgreen;">竞价成功</span>','<span style="color:red;">竞价失败</span>','<span style="color:darkgreen;">交割成功</span>','<span style="color:orange;">内部竞价</span>']);
                $v['r.otype'] = $fun->getStatus($v['otype'],['预定','预释放']);
                $v['r.money'] = $v['money'];
            }
            return json(['total'=>$total,'rows'=>$list]);
        }

        $get = $this->request->get();
        $source = 0;
        if (isset($get['dialog']) && $get['dialog'] == 1) {
            $source = 1;
        }
        $get['id'] = (isset($get['id']) && $get['id'] !== '0') ? intval($get['id']) : '';
        $end_time = '无';
        $api_id = '无';
        $type = '无';
        if ($get['id']) {
            $info = Db::name('domain_auction_info')->field('end_time,api_id,type')->where('id',$get['id'])->find();
            $end_time = date('Y-m-d H:i:s',$info['end_time']);
            if ($info['api_id']) {
                $data = $this->getApis();
                $api_id = $data[$info['api_id']];
            }
            if ($info['type'] == 0) {
                $type = '预定';
            }else if($info['type'] == 1){
                $type = '预释放';
            }
        }
        $this->view->assign(['id'=>$get['id'],'source'=>$source,'end_time'=>$end_time,'api_id'=>$api_id,'type'=>$type]);
        return $this->view->fetch();
    }

}
