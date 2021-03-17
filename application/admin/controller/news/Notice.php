<?php

namespace app\admin\controller\news;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 系统公告
 *
 * @icon fa fa-user
 */
class Notice extends Backend
{

    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_gg');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法

        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                    ->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
               $list[$k]['zt'] = $fun->getStatus($v['zt'],['--','正常展示','不展示']);
               $list[$k]['tit'] = $fun->returntitdian($v['tit'],20);
               $list[$k]['type'] = $fun->getStatus($v['type'],['普通','活动','推荐']);
               $list[$k]['alink'] = SPREAD_URL;
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
                if(empty($params['tit'])){
                    $this->error('标题必须填写！');
                }
                if($params['sj'] == ''){
                    $params['sj'] = date('Y-m-d H:i:s');
                }
                $params['bh'] = time().'g'.rand(10,99);
                $params['uip'] = $this->request->ip();
                $this->model->insert($params);
                $this->success('添加成功');       
            }
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
            if ($params){
                if(empty($params['tit'])){
                    $this->error('标题必须填写！');
                }
                if($params['sj'] == ''){
                    $params['sj'] = date('Y-m-d H:i:s');
                }
                $this->model->update($params);    
                $this->success('修改成功');       
            }
        }
        $data = $this->model->field('id,tit,wkey,wdes,txt,sj,djl,zt,type')->find($ids);
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this->model ->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }


}
