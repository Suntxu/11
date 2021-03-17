<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 推广用户
 *
 * @icon fa fa-user
 */
class Prouser extends Backend
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
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('domain_promotion_relation_log')->alias('r')->join('domain_user u','r.userid=u.id','left')->join('domain_promotion p','p.userid=r.relation_id','left')->join('domain_user u1','p.userid=u1.id','left')
                    ->where($where)
                    ->count();
            $field = ',(select sum(money1) from '.PREFIX.'domain_dingdang where userid=r.userid and ifok = 1 and sj between from_unixtime(r.stime) and from_unixtime(r.etime) ) as zje';
            $list = Db::name('domain_promotion_relation_log')->alias('r')->join('domain_user u','r.userid=u.id','left')->join('domain_promotion p','p.userid=r.relation_id','left')->join('domain_user u1','p.userid=u1.id','left')
                    ->field('u1.uid as uuid,r.stime,r.etime,u.uid,r.userid'.$field)
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_promotion_relation_log r left join '.PREFIX.'domain_dingdang d on r.userid=d.userid where d.ifok = 1 and d.sj between FROM_UNIXTIME(r.stime,"%Y-%m-%d %H:%i:%S") and FROM_UNIXTIME(r.etime,"%Y-%m-%d %H:%i:%S")';
            }else{
                $conm = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_promotion_relation_log r left join  '.PREFIX.'domain_user u on u.id=r.userid left join '.PREFIX.'domain_promotion p on p.userid=r.relation_id left join '.PREFIX.'domain_user u1 on p.userid=u1.id left join '.PREFIX.'domain_dingdang d on r.userid=d.userid '.$sql.' and d.ifok = 1 and d.sj between FROM_UNIXTIME(r.stime,"%Y-%m-%d %H:%i:%S") and FROM_UNIXTIME(r.etime,"%Y-%m-%d %H:%i:%S")';
            }
            $res = Db::query($conm);
            $zje = sprintf('%.2f',$res[0]['n']);
            //根据条件统计总金额
            $fun = Fun::ini();
            foreach($list as $k => $v){
                // 充值金额
                $list[$k]['cji'] = sprintf('%.2f',$v['zje']);
                $list[$k]['zje'] = $zje;
                $list[$k]['u1.uid'] = $v['uuid'];
                $list[$k]['u.uid'] = $v['uid'];
                $list[$k]['r.etime'] = $v['etime'];
                $list[$k]['r.stime'] = $v['stime'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('id',$this->request->get('u1.uid'));
        return $this->view->fetch();
    }
}



