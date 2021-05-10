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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->db->where($where)->count();
            $list = $this->db
                    ->field('id,uid,uqq,type,nickname,desc,create_time,money,cnstatus,status')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
                $list[$k]['type'] = $fun->getStatus($v['type'],['功能改进','在线提问','其他']);
                $list[$k]['status'] = $fun->getStatus($v['status'],['<span style="color:red">未阅读</span>','<span style="color:green">已阅读</span>']);
                $list[$k]['cnstatus'] = $fun->getStatus($v['cnstatus'],['<span style="color:red">未采纳</span>','<span style="color:green">已采纳</span>']);
                if($v['cnstatus'] == 0){
                  $list[$k]['money'] = "--";
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

}
