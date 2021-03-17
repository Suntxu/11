<?php

namespace app\admin\controller\domain\recycle;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 一键回收
 *
 * @icon fa fa-user
 */
class Recylist extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model= Db::name('recycle_task');
    }
    
    /**
     * 查看 
     */
    public function index()
    {
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();

            if($group){
                $def = 't.id = '.str_replace('HS', '', $group);
            }else{
                $def = '';
            }
            $total = $this->model->alias('t')->join('domain_user u','u.id=t.userid')
                        ->where($def)->where($where)
                        ->count();
            
            $list = $this->model->alias('t')->join('domain_user u','u.id=t.userid')
                        ->field('t.id,t.amount_ok,t.money,t.status,t.amount_no,t.create_time,t.amount,t.audit_time,u.uid')
                        ->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)
                        ->select();
           
            $fun = Fun::ini();

            foreach($list as &$v){
                $v['t.status'] = $fun->getStatus($v['status'],['<font color="red">检测中</font>','<font color="orange">检测完成</font>','<font color="green">已提交回收</font>','<font color="gray">已超时</font>','<font color="gray">已取消</font>','<font color="red">已拒绝</font>','<font color="green">已接收</font>']); 
                $v['t.money'] = $v['money'];
                $v['t.create_time'] = $v['create_time'];
                $v['group'] = 'HS'.$v['id'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    

}
