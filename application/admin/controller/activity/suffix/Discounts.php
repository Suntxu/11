<?php

namespace app\admin\controller\activity\suffix;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 注册后缀优惠
 *
 * @icon fa fa-user
 */
class Discounts extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('suffix_discounts_config');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();

            $list = $this->model->where($where)->order($sort,$order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['status'] = $fun->getStatus($v['status'],['开启','关闭']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a"); 
            if ($params){
                if(empty($params['hz']) || empty($params['ymoney']) || empty($params['money']) || empty($params['num']) || empty($params['stime']) || empty($params['etime']) ){
                    $this->error('请填写必填参数');
                }
                $params['create_time'] = time();
                $params['stime'] = strtotime($params['stime']);
                $params['etime'] = strtotime($params['etime']);
                $this->model->insert($params);
                $this -> success('添加成功');       
            }
        }
        $hz = Db::name('domain_houzhui')->where(['zt' => 1])->column('name1');
        $this->view->assign('hz',$hz);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a"); 
            if ($params){
                if(empty($params['hz']) || empty($params['ymoney']) || empty($params['money']) || empty($params['num']) || empty($params['stime']) || empty($params['etime']) ){
                    $this->error('请填写必填参数');
                }
                $params['stime'] = strtotime($params['stime']);
                $params['etime'] = strtotime($params['etime']);
                $this->model->update($params);
                $this -> success('修改成功');       
            }
        }
        $hz = Db::name('domain_houzhui')->where(['zt' => 1])->column('name1');
        $data = $this->model->find($ids);
        $this->view->assign(['data' => $data,'hz' => $hz]);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this->model->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }


}
