<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 管理员操作 -用户提现记录
 *
 * @icon fa fa-user
 */
class Tx extends Backend
{
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        $this->model = Db::name('domain_tixian');
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('t')->join('domain_user u','t.userid=u.id')->join('admin a','t.admin_id = a.id')
                        ->where($where)
                        ->count();
            
            $list = $this->model->alias('t')->join('domain_user u','t.userid=u.id')->join('admin a','t.admin_id = a.id')
                        ->field('u.uid,t.money1,t.sj,t.txyh,t.zt,t.sm,t.id,t.type,a.nickname,t.sm')
                        ->where($where)->order($sort,$order)->limit($offset, $limit)
                        ->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(t.money1) as n FROM '.PREFIX.'domain_tixian t inner join '.PREFIX.'admin a on a.id=t.admin_id where t.zt = 1';
            }else{
                $conm = 'SELECT sum(t.money1) as n FROM '.PREFIX.'domain_tixian as t left join '.PREFIX.'domain_user as u on t.userid=u.id  inner join '.PREFIX.'admin a on a.id=t.admin_id '.$sql.' and t.zt = 1 ';
            }
            $res = Db::query($conm);
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['u.uid'] = $v['uid'];
                $v['t.sj'] = $v['sj'];
                $v['t.type'] = $fun->getStatus($v['type'],['普通提现','<span style="color:red;">注销提现</span>']);
                $v['t.money1'] = sprintf('%.2f',$v['money1']);
                $v['t.txyh'] = $v['txyh'];
                $v['t.txyh'] = $v['txyh'];
                $v['t.zt'] = $fun->getStatus($v['zt'],["--","<span style='color:blue'>提现成功</span>","<span style='color:gray'>用户已经撤销提现</span>","<span style='color:red'>提现失败".$v['sm']."</span>","<span style='color:green'>等待受理</span>"]);
                $v['zje'] = $res[0]['n'];
                $v['a.nickname'] = $v['nickname'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
       
        return $this->view->fetch();
    }
}

 



