<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Proindent extends Backend
{


    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_order');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {   

        //设置过滤方法
        $ids = $this ->request->get('userid');
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if(empty($ids)){
               $auth_where = 'c.topid != 0 and  c.sptype=1';
            }else{
                $auth_where = "c.sptype=1 and c.topid = ".intval($ids);
            }
            $total = $this->model->alias('c')->join('admin a','c.topid=a.id','left')->join('domain_user u','c.userid=u.id','left')->join('spread_channel c1','u.channel=c1.id','left')
                    ->where($where)->where($auth_where)->where('c.status in (0,1) ')
                    ->count();
            $list = $this->model->alias('c')->join('admin a','c.topid=a.id','left')->join('domain_user u','c.userid=u.id','left')->join('spread_channel c1','u.channel=c1.id','left')
                    ->field('c.id,c.tit,c.tmoney,c.money,c.final_money,c.paytime,c.status,a.username,u.uid,c1.name as ydmc,c.bc,c.pack,c.id as cid,c.type,c.is_sift')
                    ->where($where)->where($auth_where)->where(' c.status in (0,1) ')
                    ->order('c.'.$sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $nu = $this->model->alias('c')->join('admin a','c.topid=a.id','left')->join('domain_user u','c.userid=u.id','left')->join('spread_channel c1','u.channel=c1.id','left')
                    ->field('sum(c.money) as n,sum(c.final_money) as f,sum(c.tmoney) as t')
                    ->where($where)->where($auth_where)->where('c.status in (0,1) ')
                    ->find();
            $fun = Fun::ini();
            foreach($list as $k => $v){

               if($v['type'] == 9){
                   $list[$k]['tit'] .= '<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showPack('.$v['cid'].')" >查看更多</span>';
               }
               $list[$k]['c.type'] = $fun->getStatus($v['type'],['正常订单','满减订单','微信活动订单',9=>'打包域名订单']);
               $list[$k]['zje'] = $nu['n'];
               $list[$k]['sfzje'] = $nu['f'];
               $list[$k]['yjzje'] = $nu['t'];
               if($v['status'] == 1 ){
                    $list[$k]['c.status'] = '已付款';
               }elseif($v['status'] == 0 ){
                    $list[$k]['c.status'] = '未付款';
               }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $requ = $this->request->get();
        $this->view->assign([
            'cel' => empty($requ['cel']) ? '' : $requ['cel'],
            'topspreader' => empty($requ['topspreader']) ? '' : $requ['topspreader'],
            'ids' => $ids,
            'status' => empty($requ['c.status']) ? '' : $requ['c.status'],
        ]);
        return $this->view->fetch();
    }
}


