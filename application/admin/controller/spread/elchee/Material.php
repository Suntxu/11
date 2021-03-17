<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 素材管理
 *
 * @icon fa fa-user
 */
class Material extends Backend
{

    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model =Db::name('domain_promotion_material');
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
            $total = $this->model->where($where)->count();
            $list = $this->model->field('id,title,link,imgpath,status,createtime,orderby,type')->where($where)->order($sort, $order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            foreach($list as $k => $v){
                $list[$k]['type'] = $fun->getStatus($v['type'],['普通','专属']);
                $list[$k]['status'] = $fun->getStatus($v['status'],['已启用','已禁用']);
                $list[$k]['imgpath'] = '/uploads'.$v['imgpath']; 
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
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if(empty($params['title']) || empty($params['benefit1']) || empty($params['benefit2']) || empty($params['link'])){
                    $this->error('请输入必填项');
                }
                if(empty($params['imgpath'])){
                    $this->error('请上传文件');
                }
                if(!preg_match('/^(https:\/\/)|(http:\/\/).+$/i',$params['link']) ){
                    $this->error('请输入正确链接');
                }         
                $params['createtime'] = time();
                $this->model->insert($params);
                $this->success('添加成功');
            }
        }
        return $this->view->fetch();
    }
     /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if(empty($params['title']) || empty($params['benefit1']) || empty($params['benefit2']) || empty($params['link'])){
                    $this->error('请输入必填项');
                }
                if(empty($params['imgpath'])){
                    $this->error('请上传文件');
                }
                if(!preg_match('/^(https:\/\/)|(http:\/\/).+$/i',$params['link']) ){
                    $this->error('请输入正确链接');
                }
                $params['imgpath'] = '/'.ltrim($params['imgpath'],'/uploads');
                $this->model->update($params);
                $this->success('添加成功');
            }
        }
        $data = $this->model->field('id,title,link,imgpath,status,benefit1,benefit2,orderby,type')->find($ids);
        $data['imgpath'] = '/uploads'.$data['imgpath'];
        $this->view->assign("data", $data);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $this->model->delete($ids);
            $this->success('操作成功');
        }
        $this->error();
    }
}

