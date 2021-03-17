<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 后缀设置
 *
 * @icon fa fa-user
 */
class Blogroll extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {   
        parent::_initialize();
        $this->model = Db::name('blogroll');
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
            $list = $this->model->field('title,url,status,createtime,orderby,id')->where($where)->order($sort,$order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['status'] = $fun->getStatus($v['status'],['启用','禁用']);
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
                if(empty($params['title']) || empty($params['url'])){
                    return $this->error('请输入必填项');
                }
                if(!preg_match('/^(https:\/\/)|(http:\/\/).+$/i',$params['url']) ){
                    $this->error('请输入正确链接');
                }   
                if($params['orderby'] && intval($params['orderby']) > 9999 ){
                    return $this->error('请输入9999以内的排序');
                }
                $params['createtime'] = time();
                $this->model->insert($params);
                $redis = new Redis();
                $redis->del('init_data_link');
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['title']) || empty($params['url'])){
                    return $this->error('请输入必填项');
                }
                if(!preg_match('/^(https:\/\/)|(http:\/\/).+$/i',$params['url']) ){
                    $this->error('请输入正确链接');
                }   
                if($params['orderby'] && intval($params['orderby']) > 9999 ){
                    return $this->error('请输入9999以内的排序');
                }
                $this->model->update($params);
                $redis = new Redis();
                $redis->del('init_data_link');
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->field('title,url,orderby,status,orderby,id')->where(['id'=>$ids])->find();
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this->model->delete($ids);
           $redis = new Redis();
           $redis->del('init_data_link');
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
    }
}
