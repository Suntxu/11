<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 邮箱验证码发送记录
 *
 */
class Alteration extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('accounts_update');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->alias('a')->join('domain_user u','u.id = a.userid')->where($where)->count();

            $list = $this->model->alias('a')->join('domain_user u','u.id = a.userid')
                ->field('a.id,a.rz_id,a.time,a.update_time,a.status,a.reason,a.check_reason,a.old_rz_id,a.ip,u.uid,a.oper')
                ->where($where)
                ->order($sort,$order)->limit($offset, $limit)
                ->select();

            $fun = Fun::ini();

            foreach($list as &$v){
                $v['a.oper'] = $fun->getStatus($v['oper'],['<span style="color:red;">用户提交</span>','<span style="color:green;">客服提交</span>']);

                $v['a.status'] = $fun->getStatus($v['status'],['<span style="color:red;">已提交</span>','<span style="color:green;">审核通过</span>','<span style="color:red;">审核未通过</span>']);

                $v['a.time'] = $v['time'];


                $v['rz_id'] = '<a href="/admin/vipmanage/realaudit/edit?ids='.$v['rz_id'].'" class="dialogit" title="实名信息查看">查看</a>';

                if($v['old_rz_id']){
                    $v['old_rz_id'] = '<a href="/admin/vipmanage/realaudit/edit?ids='.$v['old_rz_id'].'" class="dialogit" title="实名信息查看">查看</a>';
                }else{
                    $v['old_rz_id'] = '--';
                }


                if(mb_strlen($v['reason']) > 15){
                    $v['reason'] = $fun->returntitdian($v['reason'],15).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['reason'].'\')" >查看更多</span>';
                }

                if(mb_strlen($v['check_reason']) > 15){
                    $v['check_reason'] = $fun->returntitdian($v['check_reason'],15).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['check_reason'].'\')" >查看更多</span>';
                }

            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 审核
     */
    public function audit(){

        if($this->request->isAjax()){
            $params = $this->request->post();

            if(empty($params['id']) || empty($params['check_reason'])){
                $this->error('缺少重要参数');
            }

            if(!isset($params['status']) || !in_array($params['status'], [1,2])){
                $this->error('状态值不在可取值范围内');
            }


            $info = $this->model->field('rz_id,userid')->where(['id' => $params['id'],'status' => 0])->find();

            if(empty($info)){
                $this->error('记录不存在或记录已被审核,请刷新!');
            }

            $params['admin_id'] = $this->auth->id;
            $params['update_time'] = time();

            Db::startTrans();
            try{
                if($params['status'] == 1){
                    Db::name('user_renzheng')->where('userid',$info['userid'])->setField('default',0);
                    Db::name('user_renzheng')->where('id',$info['rz_id'])->setField('default',1);
                }

                $this->model->update($params);

            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }

            Db::commit();
            $this->success('操作成功');

        }

    }
    // 查询当前用户名
    public function modify()
    {
        if ($this->request->isAjax())
        {
            $params = $this->request->post();
            if(empty($params['username'])){
                $this->error('用户名不能为空');
            }
            $user=Db::name('domain_user')->where('uid',$params['username'])->field('id')->find();
            if(empty($user)){
                $this->error('用户名不存在');
            }
            $list= Db::name('user_renzheng')
                  ->field('id,renzheng,status,xing,ming,busname,default')
                  ->where(['userid' => intval($user['id']),'status' => 2])
                  ->select();
            if(empty($list)){
                $this->error('当前查找数据不存在');
            }
            foreach($list as $k=>$v){
                if($v['renzheng'] == 0){
                    $list[$k]['group'] ="[个人]".$v['xing'].$v['ming'];
                }else{
                    $list[$k]['group'] ="[企业]".$v['xing'].$v['ming']."[".$v['busname']."]";
                }
            }
            return json($list);
        }
        return $this->view->fetch();
    }



    // 客服变更
    public function customerEdit()
    {
         if ($this->request->isPost())
        {
            $params = $this->request->post('row/a');

            if(empty($params['username'])){
                $this->error('用户名不能为空');
            }
            if(empty($params['bg_id'])){
                $this->error('变更账户所有者不能为空');
            }
            if(empty($params['reason'])){
                $this->error('变更原因不能为空');
            }

            $user = Db::name('domain_user')->where('uid',$params['username'])->field('id')->find();
            if(empty($user)){
                $this->error('用户名不存在');
            }

            $bg_user = Db::name('user_renzheng')->where(['id'=>intval($params['bg_id']),'status'=>2])->field('userid')->find();
            if(empty($bg_user)){
                $this->error('变更账户所有者不存在');
            }

            if($bg_user['userid']!=$user['id']){
                $this->error('用户名不匹配');
            }
            $list= Db::name('user_renzheng')
                  ->field('id,userid')
                  ->where(['userid'=>intval($user['id']),'status'=>2,'default'=>1])
                  ->find();
            if(empty($list)){
                $this->error('账户当前所有者不存在');
            }
            if($params['bg_id']==$list['id']){
                $this->error('账户所有者相同');
            }

            $time =time();
            $ip = $this->request->ip();
            $admin_id = $this->auth->id;

            Db::startTrans();
            try{
                $this->model->insert(['userid'=>$list['userid'],'rz_id'=>$params['bg_id'],'time'=>$time,'update_time'=>$time,'status'=>1,'reason'=> $params['reason'],'old_rz_id'=>$list['id'],'ip'=>$ip,'admin_id'=>$admin_id,'oper'=>1]);

                Db::name('user_renzheng')->where('userid',$user['id'])->setField('default',0);
                Db::name('user_renzheng')->where('id',$params['bg_id'])->setField('default',1);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();
            $this->success('变更账户成功');
        }
    }


}
