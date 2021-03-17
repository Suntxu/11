<?php

namespace app\admin\controller\advertising;

use app\common\controller\Backend;
use think\Db;
use fast\Tree;
/**
 * 广告位
 *
 * @icon fa fa-user
 */
class Ad extends Backend
{

    protected $model = null;
    protected $categorydata = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('category');
        $tree = Tree::instance();
        $tree->init(collection($this->model->where(['status'=>'normal','type'=>'ad'])->order('weigh asc')->select())->toArray(), 'pid');
        $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = [0 => ['type' => 'all', 'name' => __('None')]];
        foreach ($this->categorylist as $k => $v)
        {
            $categorydata[$v['id']] = $v;
        }
        array_shift($categorydata);
        $this -> categorydata = $categorydata;
        $this->view->assign("parentList", $categorydata);
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            $result = array("total" => count($this->categorydata), "rows" => $this->categorydata);
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
            $this->request->filter(['strip_tags']);
            $params = $this->request->post("row/a");
            $pd = explode('||',$params['pid']);
            if($pd[1] == 0){
                $params['pid'] = $pd[0];
            }else{
                $params['pid'] = $pd[1];
            } 
            if($pd[1] == 0 && $pd[0] == 0){
                $params['pid'] = 0;
            }  
            $params['type'] = 'ad';   
            $this->model->insert($params);
            $this->success('添加成功');       
        }
        return $this->view->fetch();
    }
     /**
     * 添加
     */
    public function edit($ids='')
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            $this->model->update($params);    
            $this->success('修改成功');       
        }
        $data = $this->model->find($ids);
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $num = $this->model->where(['pid'=>$ids])->count();
            if($num){
               $this->model->where(['pid'=>$ids])->delete(); 
           }
            $this->model->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }


}
