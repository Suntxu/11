<?php

namespace app\admin\controller\spread\booking;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

class Offered extends Backend
{
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('assemble_order');
    }
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list   = $this->model->alias('a')->join('domain_user b','a.uid=b.id','left')
                    ->field('a.*,b.uid')
                    ->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $total  = $this->model->alias('a')->join('domain_user b','a.uid=b.id','left')
                    ->field('a.*,b.uid')
                    ->where($where)
                    ->count();
            $fun = Fun::ini();
            foreach($list as $key=>$value)
            {
                $list[$key]['b.uid']    =   $value['uid'];
                if ($value['status']    ==  -1)
                {
                    $list[$key]['status']   = "活动失败";
                }else{
                    $list[$key]['status']   =   $fun->getStatus($value['status'],['认领成功','未注册完','已完成']);
                }
            }
            $result = array("total" => $total,"rows" => $list);
            return  json($result);
        }
        return $this->view->fetch();
    }
    public function edit($ids=''){
        $list   = $this->model->alias('a')->join('domain_user b','a.uid=b.id','left')
                ->field('a.*,b.uid')
                ->where('a.id',$ids)
                ->find();
        $this->assign('data',$list);
        return  $this->view->fetch();
    }
    public function del($ids    =   '')
    {
        $id['id']   =   $ids;
        $list   =   Db::name('assemble_suffix')->delete($id);
        if (isset($list))
        {
            $tid    =   0;
            $log    =   'ID:'.$ids." 参团记录删除";
            $type   =   0;
            $admin  =   session('admin');
            $admin_id   =   $admin['id'];
            $admin_name   =   $admin['username'];
            $this->booking_log($tid,$log,$type,$admin_id,$admin_name);
            return  $this->success('删除成功');
        }
        else{
            return  $this->error("删除失败");
        }
    }
}
