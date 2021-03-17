<?php

namespace app\admin\controller\domain\recycle;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 回收经纪人
 *
 * @icon fa fa-user
 */
class Broker extends Backend
{
    protected $model = null;
        public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('user_broker');
    }
    
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->where('type',0)->count();

            $list = $this->model->where($where)->where('type',0)->order($sort,$order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['status'] = $fun->getStatus($v['status'],['正常','禁用']);
                $v['imgpath'] = '/uploads'.$v['imgpath'];
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
                if(empty($params['name']) || empty($params['qq'])){
                    $this->error('请填写必填参数');
                }
                if(!preg_match('/^\d{4,12}$/', $params['qq'])){
                    return $this->error('请输入正确的QQ号');
                }
                $params['imgpath'] = str_replace('/uploads','', strstr($params['imgpath'],'?',true) );
                $params['create_time'] = time();

                $this->model->insert($params);
                $this -> success('添加成功');       
            }
        }
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
                if(empty($params['name']) || empty($params['qq'])){
                    $this->error('请填写必填参数');
                }
                if(!preg_match('/^\d{4,12}$/', $params['qq'])){
                    return $this->error('请输入正确的QQ号');
                }
               
                $params['imgpath'] = str_replace('/uploads','', strstr($params['imgpath'],'?',true) );

                $this->model->update($params);
                $this -> success('添加成功');       
            }
        }
        $data = $this->model->find($ids);
        $this->view->assign('data' , $data);
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
