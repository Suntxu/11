<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 靓号列表
 *
 * @icon fa fa-user
 */
class Goodaccount  extends Backend
{

    protected $model = null;
    
    public function _initialize()
    {
        global $remodi_db;    
        parent::_initialize();
        $this->model = Db::connect($remodi_db);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) { 
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->name('keep_account')->where($where)->count();

            $list = $this->model->name('keep_account')->field('account,status,type,id')->where($where)->select(); 

            $fun = Fun::ini();

            foreach($list as &$v){
                $v['a_type'] = $v['type'];
                $v['type'] = $fun->getStatus($v['type'],['店铺']);
                $v['status'] = $fun->getStatus($v['status'],['未使用','已使用']);
            }   

            $result = array("total" => $total, "rows" => $list);
            
            return json($result);
        }

        return $this->view->fetch();
    }


    /**
     * 使用历史记录
     */
    public function history(){

        if ($this->request->isAjax()) {   
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->name('keep_account_operate_record')
                        ->where($where)
                        ->count();

            $list = $this->model->name('keep_account_operate_record')
                        ->field('optype,create_time,end_time,userid,account,type')
                        ->where($where)->order($sort,$order)
                        ->limit($offset, $limit)
                        ->select();

            $fun = Fun::ini();

            foreach($list as &$v){
                
                $v['optype'] = $fun->getStatus($v['optype'],['使用','过期释放','手动删除释放']);
                
                $v['create_time'] = $v['create_time'];

                $v['userid'] = $v['userid'];

                $v['end_time'] = $v['end_time'];

                $v['type'] = $fun->getStatus($v['type'],['店铺']);

            }   

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        return $this->view->fetch();



    }
}
