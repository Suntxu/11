<?php

namespace app\admin\controller\orderfx;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 工单
 *
 * @icon fa fa-user
 */
class Feedback extends Backend
{
    protected $db = null;
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        global $remodi_db;
        parent::_initialize();
        $this->db = Db::connect($remodi_db)->name('site_feedback');
    }

     /**
     * 反馈列表
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();

            $def = '';
            if($group){
                $id = str_replace('WD','',$group);
                $def = ' id = "'.($id ? $id : 0).'"';
            }

            $total = $this->db->where($def)->where($where)->count();
            $list = $this->db
                    ->field('id,uid,uqq,type,nickname,desc,create_time,money,cnstatus,status,ex_status,ex_remark')
                    ->where($def)->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
                $list[$k]['type'] = $fun->getStatus($v['type'],['功能改进','在线提问','其他']);
                $list[$k]['status'] = $fun->getStatus($v['status'],['<span style="color:red">未阅读</span>','<span style="color:green">已阅读</span>']);
                $list[$k]['eex_status'] = $v['ex_status'];
                $list[$k]['ex_status'] = $fun->getStatus($v['ex_status'],['<span style="color:red">待处理</span>','<span style="color:green">已处理</span>','<span style="color:orange">不处理</span>']);
                $list[$k]['cnstatus'] = $fun->getStatus($v['cnstatus'],['<span style="color:red">未采纳</span>','<span style="color:green">已采纳</span>']);
                $list[$k]['group'] = 'WD'.$v['id'];
                if($v['cnstatus'] == 0){
                  $list[$k]['money'] = "--";
                }
                if($v['ex_remark']){
                    $list[$k]['ex_remark'] = '<span class="show_value" style="cursor:pointer;color:#3c8dbc;" onclick="showRemark(\''.$v['ex_remark'].'\')" >查看</span>';
                }else{
                    $list[$k]['ex_remark'] = '--';
                }
                // $list[$k]['check'] = '<a href="orderfx/order/feedbackedit?ids='.$v["id"].'" class="btn btn-xs btn-success btn-editone btn-dialog" title="查看"><i class="fa fa-pencil"></i></a>';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    public function edit($ids=null)
    {
        if ($this->request->isPost()){
            $params = $this->request->post('row/a');
            if(empty($params['id'])){
                $this->error('缺少重要参数');
            }
            $this->db->where('id',intval($params['id']))->update(['status'=>$params['status'],'cnstatus'=>$params['cnstatus'],'money'=>$params['money']]);
            $this->success('状态修改成功');
        }
        $data=$this->db->where('id',intval($ids))->field('id,status,cnstatus,money,uid,uqq,type,nickname,desc,img_path')->find();
        if($data['img_path']){
            $data['img_path'] = explode(',',$data['img_path']);
        }

        $this->assign('data',$data);
        return $this->view->fetch();
    }

    public function modi(){
        if($this->request->isAjax()){
            $param = $this->request->param();
            if(empty($param['id']) || empty($param['status'])){
                $this->error('缺少重要参数');
            }
            if(!in_array($param['status'],[1,2])){
                $this->error('参数不在合法范围');
            }
            $flag = $this->db->where(['id' => $param['id'],'ex_status' => 0])->count();
            if(empty($flag)){
                $this->error('记录不存在或状态已发生改变');
            }
            $remark = empty($param['remark']) ? '' : $param['remark'];
            $this->db->where('id',$param['id'])->update(['ex_status' => $param['status'],'ex_remark' => $remark]);
            $this->success('操作成功');

        }
    }
}
