<?php

namespace app\admin\controller\fangfeng;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 防封设置
 *
 * @icon fa fa-user
 */
class Salelog extends Backend
{
    protected $relationSearch = false;
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;
    public function _initialize()
    {   
        parent::_initialize();
        $this->model = Db::name('fangfeng_sale');
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
            $list = $this->model->field('username,qq,token,server_type,start_time,end_time,sale_time,money,remark,id,status')->where($where)->order($sort,$order)->limit($offset, $limit)->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money) as n FROM '.PREFIX.'fangfeng_sale';
            }else{
                $conm = 'SELECT sum(money) as n FROM '.PREFIX.'fangfeng_sale'.$sql;
            }
            $res = Db::query($conm);
            $zje = sprintf('%.2f',$res[0]['n']);
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['server_type'] = $fun->getStatus($v['server_type'],['包月','包年']);
                $v['status'] = $fun->getStatus($v['status'],['启用','禁用']);
                $v['zje'] = $zje;
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
                if(empty($params['username']) || empty($params['qq']) || empty($params['start_time']) || empty($params['sale_time']) || empty($params['money']) || empty($params['end_time']) ){
                    return $this->error('请输入必填项');
                }
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);
                $params['sale_time'] = strtotime($params['sale_time']);
                $params['token'] = md5('MD'.time());
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
               if(empty($params['username']) || empty($params['qq']) || empty($params['start_time']) || empty($params['sale_time']) || empty($params['money']) || empty($params['end_time']) ){
                    return $this->error('请输入必填项');
                }
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);
                $params['sale_time'] = strtotime($params['sale_time']);
                $this->model->update($params);
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->field('username,qq,token,server_type,start_time,end_time,sale_time,money,remark,id,status')->where(['id'=>$ids])->find();
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
