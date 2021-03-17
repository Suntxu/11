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
class Recydetail extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model= Db::name('recycle_detail');
    }
    /**
     * 查看 
     */
    public function index()
    {
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();

            if($group){
                $def = 'd.tid = '.str_replace('HS', '', $group);
            }else{
                $def = '';
            }

            $total = $this->model->alias('d')->join('recycle_task t','t.id=d.tid')->join('domain_user u','u.id=t.userid')->where($def)->where($where)->count();
            
            $list = $this->model->alias('d')->join('recycle_task t','t.id=d.tid')->join('domain_user u','u.id=t.userid')
                        ->field('d.tit,d.dqsj,d.status,d.money,d.tid,d.hz,d.remark,u.uid,d.dstatus')
                        ->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)
                        ->select();

            $fun = Fun::ini();

            foreach($list as &$v){
                $v['d.status'] = $fun->getStatus($v['status'],['<font color="red">检测中</font>','<font color="green">可回收</font>','<font color="orange">不可回收</font>','<font color="gray">已取消</font>','<font color="red">回收被拒绝</font>','<font color="green">回收已接收</font>']); 
                // $v['wx_check'] = $fun->getStatus($v['wx_check'],['<font color="gray">未知</font>','<font color="green">未拦截</font>','<font color="red">拦截</font>']); 
                $v['d.money'] = $v['money'];
                $v['d.dstatus'] = $fun->getStatus($v['dstatus'],['<font color="gray">未检测</font>','<font color="green">正常</font>','<font color="red">被hold</font>','<font color="orange">被墙</font>']); 
                $v['group'] = 'HS'.$v['tid'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

}
