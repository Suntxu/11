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
class Order extends Backend
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
        $this->model = Db::name('domain_orderfx');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('o')->join('domain_helptype h','o.type=h.id')
                    ->where($where)
                    ->count();
            $list = $this->model->alias('o')->join('domain_helptype h','o.type=h.id')
                    ->field('o.*,h.name1')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
                $list[$k]['type'] = $v['name1'];
                $list[$k]['status'] = $fun->getStatus($v['status'],['未处理','处理中','已完成']);
                $list[$k]['fx_stat'] = $fun->getStatus($v['fx_stat'],['待回复','待反馈','已完结',4=>'已删除']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 查看
     */
    public function show($ids='')
    {
        //回复内容
        $id = $this->request->get('id');
        if($id){
            $params = $this->request->post();
            if(isset($params['flag'])){
                if($params['file']){
                    // 去掉/uploads/
                    $file1 = explode(',',$params['file']);
                    $fiel1 = array_map(function($v){ return ltrim($v,'/uploads/'); },$file1);
                    $params['file'] = implode(',',$fiel1).',';
                }
                Db::name('order_reply') -> insert(['con'=>$params['hf'],'time'=>time(),'gid'=>$params['gid'],'author'=>'怀米工程师','file'=>$params['file']]);
                Db::name('domain_orderfx') -> where(['id'=>$params['gid']]) -> update(['fx_stat'=>1]);
                $this -> success('回复成功','/admin/orderfx/Order/show?id='.$id);
            }
            $data = Db::name('domain_orderfx')->where([ 'id'=> $id])->find();
            $data['type'] = Db::name('domain_helptype')->where('id',$data['type'])->value('name1');

            if($data['status'] === 0){
                Db::name('domain_orderfx')->where(['id'=>$data['id']])->setField('status',1);
            }
            $data['status'] = Fun::ini()->getStatus($data['status'],['未处理','处理中','已完成']);
            $data['fx_stat1'] = Fun::ini()->getStatus($data['fx_stat'],['待回复','待反馈','已完结',4=>'已删除']);
            if(!empty($data['file'])){
                $img = explode(',',rtrim($data['file'],','));
            }else{
                $img = [];
            }
            $data['img'] = '';
           foreach($img as $v){
                $data['img'] .= '<img src="'.WEBURL.'uploads/workorder/'.$v.'" onclick="bigImg(this,'.$data['id'].')"  style="width: 29px;height: 29px;cursor: pointer;" >';
           }
           $data['ig'] = '<a id="a'.$data['id'].'"><img id="bigimg'.$data['id'].'" style="cursor: pointer; max-width: 240px; max-height: 200px;"></a>';
           //沟通记录‘
           $hf = '';
           $user = Db::name('order_reply') -> where(['gid'=>$data['id']])->select();
           foreach($user as $k => $v){
                if($v['author'] != '怀米工程师'){
                    $hf .= '<div class="jllist user " >';
                }else{
                    $hf .= '<div class="jllist">';
                }
                if($v['author'] != '怀米工程师'){
                     $topimg = Db::name('domain_user')->where(['uid'=>$v['author']])->value('topimg');
                     $hf .= '<img class="touxiang" src="';
                     if($topimg){
                        $hf .= WEBURL.'uploads/headimg/'.$topimg.'">';
                     }else{
                        $hf .= WEBURL.'static/images/header.png">';
                     }
                     $hf .= '<div class="guke">'.$v['author'].'  '.$v['con'];
                     if(!empty($v['file'])){
                        $img = explode(',',rtrim($v['file'],','));
                     }else{
                        $img =[];
                     }
                     foreach($img as $kk => $vv){
                        $hf .= ' <img src="'.WEBURL.'uploads/workorder/'.$vv.'" onclick="bigImg(this,'.$v['id'].')"  style="width: 29px;height: 29px;cursor: pointer;" >';
                     }
                }else{
                    $hf .= '<img class="touxiang" src="'.WEBURL.'static/images/image/hm.jpg" />';
                    $hf .= '<div class="guke">'.$v['author'].' &nbsp;&nbsp; '.$v['con'];
                     if(!empty($v['file'])){
                        $img = explode(',',rtrim($v['file'],','));
                     }else{
                        $img =[];
                     }
                     foreach($img as $kk => $vv){
                        $hf .= ' <img src="/uploads/'.$vv.'" onclick="bigImg(this,'.$v['id'].')"  style="width: 29px;height: 29px;cursor: pointer;" >';
                     }
                }
                $hf .= ' <p><span class="time dtsj">'.date('Y-m-d  H:i:s',$v['time']).'</span></p></div><div><br><a id="a'.$v['id'].'"><img id="bigimg'.$v['id'].'" style="cursor: pointer; max-width: 320px; max-height: 280px;"></a></div></div>';
            }
            $this->view->assign(['data'=>$data,'hf'=>$hf]);
            return $this->view->fetch();
        }
        return $this->view->fetch();
    }


     /**
     * 反馈列表
     */
    public function feedback()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->db->where($where)->count();
            $list = $this->db
                    ->field('id,uid,uqq,type,nickname,desc,create_time,status')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
                $list[$k]['type'] = $fun->getStatus($v['type'],['功能改进','在线提问','其他']);
                $list[$k]['y_status'] = $v['status'];
                $list[$k]['status'] = $fun->getStatus($v['status'],['<span style="color: red;">未读 </span>','<span style="color: green;">已读 </span>']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

     /**
     * 反馈状态
     */
    public function ready()
    {
        if ($this->request->isAjax())
        {
            $id = $this->request->get('id');

            if(empty($id)){
                $this->error('缺少重要参数');
            }

            $this->db->where('id',intval($id))->setField('status',1);

            $this->success('消息已读');
        }
    }

}
