<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
/**
 * 一口价交易分析
 *
 */
class Analysis extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['varyMap'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('storeconfig');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$paytime) = $this->buildparams();


            $def = ' s.flag = 1';
            $total = $this->model->alias('s')->join('domain_user u','s.userid=u.id')
                          ->where($where)->where($def)
                          ->count();

            $list = $this->model->alias('s')->join('domain_user u','s.userid=u.id')
                    ->field('u.uid,s.shopname,s.userid')
                    ->where($where)->where($def)->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $userids = array_column($list,'userid');
            $info = $this->getOrderTotal($userids,$paytime);
            $zje = 0;
            $znum = 0;
            $zsale = 0;

            foreach($list as &$v){
              
              $v['u.uid'] = $v['uid'];
              $v['show'] = '查看';
              $v['group'] = $paytime;
              if(isset($info[$v['userid']])){
                  $v['salenum'] = $info[$v['userid']]['salenum'];
                  $v['num'] = $info[$v['userid']]['num'];
                  $v['salemoney'] = sprintf('%.2f',$info[$v['userid']]['salemoney']);
              }else{
                  $v['salenum'] = 0;
                  $v['num'] = 0;
                  $v['salemoney'] = 0;
              }
              $zje += $v['salemoney'];
              $znum += $v['num'];
              $zsale += $v['salenum'];
              $v['zje'] = $zje;
              $v['znum'] = $znum;
              $v['zsale'] = $zsale;

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 获取数据
     */
    public function varyMap(){
        if($this->request->isAjax()){

            $paytime = $this->request->post('ptime');
            $list = $this->model->alias('s')->join('domain_user u','s.userid=u.id')
                ->field('s.shopname,s.userid ')
                ->where('s.flag = 1')->order('s.userid','asc')
                ->select();
            $userids = array_column($list,'userid');
            $info = $this->getOrderTotal($userids,$paytime);
            $data = [];
            
            foreach($list as $v){
                if(isset($info[$v['userid']])){
                    $data['salenum'][] = $info[$v['userid']]['salenum'];
                    $data['num'][] = $info[$v['userid']]['num'];
                    $data['salemoney'][] = $info[$v['userid']]['salemoney'];
                }else{
                    $data['salenum'][] = 0;
                    $data['num'][] = 0;
                    $data['salemoney'][] = 0;
                }
                $data['shopname'][] = $v['shopname'];
            }

            $data['maxmoney'] = max($data['salemoney']);
            $data['maxsnum'] = max($data['salenum']);
            $data['maxnum'] = max($data['num']);
            return json_encode($data);
        }

    }
    /**
     * 详情页
     */
    public function show(){

      if($this->request->isAjax()){

        list($where, $sort, $order, $offset, $limit) = $this->buildparams();

        $def = 'o.status = 1';

        $total = Db::name('domain_order')->alias('o')->join('domain_user u','o.userid=u.id')
                      ->where($where)->where($def)
                      ->group('o.userid')
                      ->count();

        $list = Db::name('domain_order')->alias('o')->join('domain_user u','o.userid=u.id')
                ->field('u.uid,sum(money) as salemoney,count(*) as salenum,o.paytime')
                ->where($where)->where($def)->order($sort, $order)
                ->limit($offset, $limit)
                ->group('o.userid')
                ->select();

        $result = array("total" => $total, "rows" => $list);
        return json($result);
      }
      $this->view->assign('userid',$this->request->get('userid'));
      return $this->view->fetch();
    }

    /**
     * 格式化时间搜索
     */
    private function parseTime($paytime){
        $timeSearch = '';
        if($paytime){
            $times = explode(' - ',$paytime);
            if(isset($times[0]) && isset($times[1])){
                $timeSearch = ' and paytime between "'.$times[0].'" and "'.$times[1].'" ';
            }elseif(isset($times[0])){
                $timeSearch = ' and paytime >= "'.$times[0].'"';
            }elseif(isset($times[1])){
                $timeSearch = ' and paytime <= "'.$times[1].'" ';
            }
        }
        return $timeSearch;
    }
    /**
     * 订单统计
     */
    private function getOrderTotal($userids,$paytime){
        $arr = [];
        $timeSearch = $this->parseTime($paytime);
        $zinfo = Db::name('domain_order')->field('sum(money) as salemoney,count(distinct userid) as num,count(*) as salenum,selleruserid ')->where('status = 1 '.$timeSearch)->whereIn('selleruserid',$userids)->group('selleruserid')->select();
        foreach($zinfo as $v){
            $arr[$v['selleruserid']] = $v;
        }
        return $arr;
    }
}
