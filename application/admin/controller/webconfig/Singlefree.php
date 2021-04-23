<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;



/**
 * 单免设置
 *
 */
class Singlefree extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_singlefree');
    }

    public function index(){
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id')->join('admin a','a.id=r.admin_id')->where($where)->count();
            $list = $this->model->alias('r')
                ->join('domain_user u','r.userid=u.id')
                ->join('admin a','a.id=r.admin_id')
                ->field('r.start_time,r.over_time,r.status,r.add_time,r.free_money,u.uid,r.id,a.username')
                ->where($where)
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
                $list[$k]['r.status'] = $fun->getStatus($v['status'],['禁用','正常']);
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
                if(empty($params['over_time']) || empty($params['uid']) || empty($params['start_time'])){
                    return $this->error('请输入必填项');
                }

                if (!empty($params['free_money']) && $params['free_money'] < 0) {
                    return $this->error('转回金额不能为负数');
                }

                if (empty($params['free_money'])) {
                    $params['free_money'] = 0;
                }

                $userid = Db::name('domain_user')->where('uid',$params['uid'])->value('id');

                if(empty($userid)){
                    $this->error('输入的用户不存在');
                }

                $flag = $this->model->where(['userid' => $userid])->count();
                if($flag){
                    $this->error('请不要重复在同一个用户下面添加免费金额');
                }
                $params['start_time'] = strtotime($params['start_time']);
                $params['over_time'] = strtotime($params['over_time']);
                $params['userid'] = $userid;
                $params['add_time'] = time();
                $params['admin_id'] = $this->auth->id;
                unset($params['uid']);
                $res = $this->model->insert($params);
                if ($res) {
                    $this->success('添加成功');
                }else{
                    $this->error('添加失败');
                }

            }else{
                $this->error('缺少数据');
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL){
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['over_time']) || empty($params['start_time'])){
                    return $this->error('请输入必填项');
                }
                if (!empty($params['free_money']) && $params['free_money'] < 0) {
                    return $this->error('转回金额不能为负数');
                }
                if (empty($params['free_money'])) {
                    $params['free_money'] = 0;
                }
                $params['start_time'] = strtotime($params['start_time']);
                $params['over_time'] = strtotime($params['over_time']);
                $params['update_time'] = time();
                $params['admin_id'] = $this->auth->id;
                $res = $this->model->update($params);
                if ($res) {
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->alias('r')->join('domain_user u','r.userid=u.id','left')->field('r.start_time,r.over_time,r.status,r.add_time,r.free_money,u.uid,r.id')->where(['r.id'=>$ids])->find();

        $this->view->assign('data',$data);
        return $this->view->fetch();
    }


}