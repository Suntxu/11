<?php

namespace app\admin\controller\domain\recycle;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 回收操作
 *
 * @icon fa fa-user
 */
class Manage extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model= Db::name('domain_recycle');
    }
    
    /**
     * 查看 
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model->field('id,hz,zcs,money,mot,qq,status,create_time,update_time,email')
                        ->where($where)->order($sort,$order)->limit($offset, $limit)
                        ->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['status'] = $fun->getStatus($v['status'],['<font color="red">未联系</font>','<font color="pink">已联系</font>','<font color="green">已回收</font>','<font color="red">拒绝回收</font>']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

 
    // 详情
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){

            $params = $this->request->post("row/a");

            if ($params){
                if(empty($params['status'])){
                    $this->error('缺少审核参数');                    
                }
                $params['update_time']  = time();
                $this->model->update($params);
                $this->success('修改成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->find($ids);
       
        $data['zt'] = Fun::ini()->getStatus($data['status'],['<font color="red">未联系</font>','<font color="pink">已联系</font>','<font color="green">已回收</font>','<font color="red">拒绝回收</font>']);
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
}
