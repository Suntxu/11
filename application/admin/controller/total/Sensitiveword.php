<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 敏感词
 */
class Sensitiveword extends Backend
{

    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('sensitive_word');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model->field('id,title,type,create_time,status')->where($where)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            foreach($list as &$v){
               $v['type'] = $fun->getStatus($v['type'],['页面搜索']);
               $v['status'] = $fun->getStatus($v['status'],['<span style="color:green;">开启</span>','<span style="color:red;">关闭</span>']);

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
                if(empty($params['title'])){
                    $this->error('敏感词不能为空');
                }

                $tits = Fun::ini()->moreRow($params['title']);

                if(count($tits) > 100){
                    $this->error('敏感词一次最多提交100个域名');
                }
                array_walk($tits,function ($n){
                    if(mb_strlen($n) > 50){
                        $this->error($n.'长度不得超过50字符');
                    }
                });

               	$existsTits = $this->model->whereIn('title',$tits)->where('type',0)->column('title');
               	if($existsTits){
               		$this->error('敏感词 '.implode(',',$existsTits).' 已经存在!');
               	}
                $params['create_time'] = time();
                $inserts = array_map(function($n) use ($params) {
               	    return ['status' => $params['status'],'title' => $n,'create_time' => $params['create_time']];
                },$tits);

                $this->model->insertAll($inserts);

                $this->success('添加成功');  
            }
        }
        return $this->view->fetch();
    }

     /**
     * 编辑
     */
    public function edit($ids='')
    {

        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['title'])){
                    $this->error('敏感词不能为空');
                }
               
               	if(mb_strlen($params['title']) > 50){
               		$this->error('敏感词长度不得超过50字符');
               	}

               	$exists = $this->model->where(['type' => 0,'title' => $params['title']])->where('id != '.$ids)->count();
               	if($exists){
               		$this->error('该敏感词已经存在');
               	}
               	
                $this->model->update($params);

                $this->success('修改成功');
            }
        }

        $data = $this->model->field('id,title,status')->find($ids);
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
