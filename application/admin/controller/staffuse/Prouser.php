<?php

namespace app\admin\controller\staffuse;

use app\common\controller\Backend;
use think\Db;

/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Prouser extends Backend
{

    protected $relationSearch = false;

    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_user');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $wh = ['topspreader'=>$this->auth->id];
            $total = $this->model->alias('u')->join('spread_channel c','u.channel=c.id')
                    ->where($where)->where($wh)
                    ->count();
            $list =  $this->model->alias('u')->join('spread_channel c','u.channel=c.id')
                    ->field('u.id,u.uid,u.zt,u.sj,c.name as chenn')
                    ->where($where)->where($wh)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            $uid = $this ->auth->id;
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang WHERE topspreader ='.$uid.' AND ifok=1';
            }else{
                $conm = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang WHERE topspreader = '.$uid.' AND ifok=1 AND userid IN ( SELECT id FROM '.PREFIX.'domain_user '.$sql.' )';
            }
            $res = Db::query($conm);
            $m = Db::name('domain_dingdang');
            //上个月的起始和结束时间
            $pr_begin_time = date('Y-m-01 00:00:00',strtotime('-1 month'));
            $pr_end_time = date("Y-m-d 23:59:59", strtotime('-'.date('d').'day'));

            //本月的起始和结束时间
            $begin_time = date('Y-m-01 00:00:00');
            $end_time = date('Y-m-d H:i:s');
            foreach($list as $k => $v){
                $list[$k]['allpay'] = $m->where(['userid' => $v['id']])->where($wh)->where("ifok = 1")->sum('money1');
                //上个月充值金额
                $list[$k]['prevpay'] = $m->where(['userid' => $v['id']])->where($wh)->where("ifok = 1")->where("sj >= '{$pr_begin_time}' and sj <= '{$pr_end_time}' ")->sum('money1');
                //本月充值金额
                $list[$k]['monpay'] = $m->where(['userid' => $v['id']])->where($wh)->where("ifok = 1")->where("sj >= '{$begin_time}' and sj <= '{$end_time}' ")->sum('money1');
                //渠道名称
                $list[$k]['zje'] = $res[0]['n'];
                if($v['zt'] == 1){
                    $list[$k]['zt'] = '正常';
                }elseif($v['zt'] == 3){
                    $list[$k]['zt'] = '冻结';
                }elseif($v['zt'] == 2){
                    $list[$k]['zt'] = '邮箱未激活';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}


