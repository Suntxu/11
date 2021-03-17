<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;

/**
 * 补单记录表
 *
 * @icon fa fa-user
 */
class Replenishment extends Backend
{

    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_operate_record');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('r')->join('admin a','r.operator_id=a.id')->join('domain_dingdang d','d.ddbh=substring(r.value,9)','left')->join('domain_user u','u.id=d.userid','left')
                          ->where($where)->where(['r.type' => 3,'d.ifok' => 1])->count();
            $list = $this->model->alias('r')->join('admin a','r.operator_id=a.id')->join('domain_dingdang d','d.ddbh=substring(r.value,9)','left')->join('domain_user u','u.id=d.userid','left')
                    ->field('a.username,r.value,r.create_time,u.uid,d.money1,d.bz,d.ddbh,d.sj')
                    ->where($where)->where(['r.type' => 3,'d.ifok' => 1])
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_operate_record r left join '.PREFIX.'domain_dingdang d on d.ddbh=substring(r.value,9) where r.type = 3 and d.ifok = 1';
            }else{
                $conm = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_operate_record r left join '.PREFIX.'admin a on a.id=r.operator_id  left join '.PREFIX.'domain_dingdang d on d.ddbh=substring(r.value,9) left join '.PREFIX.'domain_user u on d.userid=u.id '. $sql .' and r.type = 3 and d.ifok = 1 ';
            }
            $res = Db::query($conm);
            foreach($list as $k => $v){
               $list[$k]['zje'] = $res[0]['n'];
               $list[$k]['r.create_time'] = $v['create_time'];
               $list[$k]['d.money1'] = $v['money1'];
               $list[$k]['u.uid'] = $v['uid'];
               $list[$k]['a.username'] = $v['username'];
               $list[$k]['d.sj'] = $v['sj'];
               $list[$k]['d.ddbh'] = $v['ddbh'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign([
          'ddbh' => $this->request->get('ddbh'),
          'uid' => $this->request->get('u.uid'),
        ]);
        return $this->view->fetch();
    }
}


