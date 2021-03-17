<?php

namespace app\admin\controller\activity\pageset;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 优惠页面元素设置
 *
 * @icon fa fa-user
 */
class Dispage extends Backend
{
    
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('discpage_config');
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

            $cates = $this->getCates('pageset',true);
            $akey = array_keys($cates);

            $total = $this->model->where($where)->whereIn('type',$akey)->count();
            $list = $this->model->field('id,title,link,create_time,type,status,money,sort')->where($where)->whereIn('type',$akey)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();

            $fun = Fun::ini();

            foreach($list as $k=>&$v){
                $v['status'] = $fun->getStatus($v['status'],['--','已启用','已禁用']);
                $v['type'] = isset($cates[$v['type']]) ? $cates[$v['type']] : '--';
            }
            $result = array("total" =>$total, "rows" => $list);
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
            $params = $this->request->post('row/a');

            // 如果没搜索的话 不能提交
            if(empty($params['title']) ){
                $this->error('请输入名称');
            }
            $params['create_time'] = time();
            $this->model->insert($params);
            $this->success('添加成功');
        }
        $cates = $this->getCates('pageset',true);
        $this->assign('cates',$cates);

        return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = NULL){

        if ($this->request->isPost()){
            $params = $this->request->post('row/a');
            
            // 如果没搜索的话 不能提交
            if( empty($params['title']) ){
                $this->error('请输入名称');
            }
            
            $this->model->update($params);

            $this->success('修改成功');
        }
        $data = $this->model->find($ids);
        $cates = $this->getCates('pageset',true);

        $this->view->assign(['data' => $data,'cates' => $cates]);
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
