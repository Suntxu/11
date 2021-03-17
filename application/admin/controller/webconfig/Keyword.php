<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 关键字设置--用户搜索
 */
class Keyword extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {   
        parent::_initialize();
        $this->model = Db::name('keyword_search');
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
            $list = $this->model->field('title,type,status,create_time,sort,id')->where($where)->order($sort,$order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['type'] = $fun->getStatus($v['type'],['域名简介','域名','店铺名称','店铺QQ']);
                $v['status'] = $fun->getStatus($v['status'],['启用','禁用']);
                if(mb_strlen($v['title']) > 15){
                    $v['title'] = $fun->returntitdian($v['title'],15).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['title'].'\')" >查看更多</span>'; 
                }
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
                if($params['sort'] && intval($params['sort']) > 9999 ){
                    return $this->error('请输入9999以内的排序');
                }
                $params['create_time'] = time();
                $this->model->insert($params);
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
                if($params['sort'] && intval($params['sort']) > 9999 ){
                    return $this->error('请输入9999以内的排序');
                }
                $this->model->update($params);
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->field('title,type,status,create_time,sort,id')->where(['id'=>$ids])->find();
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
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
    }
}
