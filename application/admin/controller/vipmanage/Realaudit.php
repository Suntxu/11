<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 实名审核
 *
 * @icon fa fa-circle-o
 * @remark 主要用于管理上传到又拍云的数据或上传至本服务的上传数据
 */
class Realaudit extends Backend
{

    protected $noNeedRight = ['maralname'];

    protected $model = null;



    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('user_renzheng');
    }


    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $def = '';
            if(!empty($group)){
                if((mb_strlen($group) > 5 && !strpos($group,'·')) || strpos($group,'公司')){
                    $def = ' r.busname like "%'.$group.'%" ';
                }else{
                    $x = mb_substr($group,0,1); //获取姓
                    $m = mb_substr($group,1);
                    $def = ' r.xing = "'.$x.'" ';
                    if(!empty($m)){
                        $def .= ' and r.ming like "'.$m.'%" ';
                    }
                }
            }
            
            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                    ->where($where)->where($def)
                    ->count();

            $list = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                    ->field('r.id,u.uid,r.renzheng,r.status,r.createtime,r.checktime,r.remark,r.title,r.xing,r.ming,r.busname')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach($list as $k=>$v){
                if($v['renzheng'] == 0){
                    $list[$k]['group'] = $v['xing'].$v['ming'];
                }else{
                    $list[$k]['group'] = $v['busname'];
                }
                $list[$k]['r.renzheng'] = Fun::ini()->getStatus($v['renzheng'],['个人认证','企业认证']);
                $list[$k]['r.status'] = Fun::ini()->getStatus($v['status'],['审核中','失败','成功',9=>'已删除']);
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['r.title'] = $v['title'];
                $msg = json_decode($v['remark'],true);
                if($msg){
                    $list[$k]['remark'] = $msg['msg'];
                }
                if ($v['status'] == 1) {
                    $list[$k]['manmage'] = "&nbsp;&nbsp;<a href='javascript:;' onclick='setStat({$v['id']})'>删除</a>";
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign([
            'id' => $this->request->get('ids'),
        ]);
        return $this->view->fetch();
    }
    /**
     * 查看
     */
    public function edit($ids = ''){
        if($this->request->isPost()){
            $params = $this->request->post('row/a');
            $id = $this->request->post('id');
            if(empty($id)){
                return $this->error('缺少重要参数');
            }

            $params['image1'] = strstr($params['image1'],'?',true);
            if(isset($params['image2'])){
                $params['image2'] = strstr($params['image2'],'?',true);
                if($params['status'] == 1){
                    $params['default'] = 0;
                }
            }

            $params['checktime'] = time();
            //增加操作员id
            $params['admin_id'] = $this->auth->id;

            $this->model->where(['id'=>$id])->update($params);
            return $this->success('操作成功');
        }   
        if(empty($ids)){
            return $this->error('缺少重要参数');
        }
        $data = $this->model->where(['id'=>intval($ids)])->find();
        $msg = json_decode($data['remark'],true);
        if($msg){
            $data['remark'] = '状态码：'.$msg['status']."\r\n错误信息：".$msg['msg']."\r\n证件号：".$msg['idCard']."\r\n姓名：".$msg['name'];
        }

        $rand = rand(100000,999999);
        $this->view->assign(['rand' => $rand,'data' => $data]);
        return $this->view->fetch();

    }

    /**
     * 手动实名认证
     */
    public function maralname(){

        if($this->request->isPost()){

            $data = $this->request->post('row/a');

            if(empty($data['uid'])){
                $this->error('请填写用户名');
            }

            if(empty($data['xing']) || empty($data['ming'])){
                $this->error('请填写姓或名');
            }

            if(empty($data['address'])){
                $this->error('请填写地址');
            }
            if(empty($data['renzhengno'])){
                $this->error('请填写身份证编号');
            }
            if(empty($data['image1'])){
                $this->error('请上传身份证照片');
            }
            if($data['renzheng'] == 1 && (empty($data['busname']) || empty($data['buslicence']) || empty($data['image2']) )){
                $this->error('请填写企业信息');
            }
            $data['userid'] = Db::name('domain_user')->where('uid',$data['uid'])->value('id');
            
            if(empty($data['userid'])){
                $this->error('用户不存在,请确认!');
            }

            // 验证身份证绑定了几个
            $sfzNum = $this->model->where('status != 9 and renzhengno = "'.$data['renzhengno'].'"')->column('userid');
            if(count($sfzNum) >= 5){
                $this->error('每张身份证最多认证5个用户(包含审核中)');
            }

            if(in_array($data['userid'],$sfzNum)){
                $this->error('该用户已认证此身份证信息');
            }


            if($data['renzheng'] == 1){
                // 验证该社会信用代码是否重复绑定或者已经绑定
                $buslicence = $this->model->where('status in(0,2) and buslicence = "'.$data['buslicence'].'"')->column('userid');
                if(count($buslicence) >= 5){
                    $this->error('每张营业执照最多认证5个用户(包含审核中)');
                }
                if(in_array($data['userid'],$buslicence)){
                    $this->error('该营业执照已经绑定到您的账户下面(包含审核中)');
                }
            }else{
                //如果是个人的清空企业信息
                $data['buslicence'] = '';
                $data['busname'] = '';
                $data['image2'] = '';
            }

            $data['status'] = 2;
            $data['createtime'] = time();
            $data['remark'] = '人工审核通过';
            $data['checktime'] = strtotime('+1 min');
            $data['admin_id'] = $this->auth->id;
            unset($data['uid']);
            $this->model->insert($data);
            $this->success('手动认证成功');

        }

        return $this->view->fetch();
    }

    /*
     * 删除
     */
    public function delrenzheng(){
        if($this->request->isPost()){
            $id = $this->request->post('id');
            if (empty($id)){
                return json(['code'=>0,'msg'=>'参数错误']);
            }
            $data = Db::name('user_renzheng')->where(['id'=>$id,'status'=>1])->value('id');
            if (!$data){
                return json(['code'=>0,'msg'=>'数据不存在或状态已更改,请刷新后重试']);
            }
            $res = Db::name('user_renzheng')->where('id',$id)->delete();
            if ($res){
                return json(['code'=>1,'msg'=>'删除成功']);
            }else{
                return json(['code'=>1,'msg'=>'删除失败']);
            }
        }

    }

}



