<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
use app\admin\common\sendMail;
/**
 * 注销用户
 */
class Canceluser extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('user_cancel');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit,$uid) = $this->buildparams();
            $def = ' 1 = 1  ';
            if($uid){
            	$def .= ' and u.uid like "%'.str_replace('@','~',trim($uid)).'%" ';
            }

            $total = $this->model->alias('c')->join('domain_user u','u.id = c.userid')
            		->where($def)->where($where)
            		->count();
            $list = $this->model->alias('c')->join('domain_user u','u.id = c.userid')
                    ->field('c.id,c.time,c.endtime,c.status,c.endtime,c.msg,c.ip,c.userid,u.uid')
                    ->where($def)->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();

            foreach($list as &$v){
            	$uids = explode('_',$v['uid']);
            	$v['group'] = isset($uids[1]) ? str_replace('~','@',$uids[1]) : $v['uid'];
            	$v['c.time'] = $v['time'];
            	$v['c.endtime'] = $v['endtime'];
            	$v['c.status'] = $fun->getStatus($v['status'],['待审核','<span style="color: red;">注销失败</span>','<span style="color: green;">注销成功</span>']);
                if(mb_strlen($v['msg']) > 10){
                    $v['msg'] = $fun->returntitdian($v['msg'],10).' <a href="javascript:showRemark(\''.$v['msg'].'\');">查看</a>';
                }
                if($v['status'] == 0){
                    $v['operate'] = '<button href="/admin/vipmanage/canceluser/audit" class="btn btn-xs btn-warning btn-magic" onclick="audit('.$v['id'].',6)" title="审核成功" data-table-id="table"><i class="fa fa-magic"></i> 成功</button>';
                    $v['operate'] .= '&nbsp;<button href="/admin/vipmanage/canceluser/audit" class="btn btn-xs btn-danger btn-magic" onclick="audit('.$v['id'].',1)"  title="审核失败" ><i class="fa fa-magic"></i> 失败</button>';
                }else{
                    $v['operate'] = '--';
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
            $param = $this->request->post();

            $id = empty($param['id']) ? 0 : intval($param['id']);
            if(isset($param['status'])){
                $status = $param['status'] == 1 ? 1  : 6;
            }
            if($id && isset($status)){
                $uinfo = Db::name('domain_user')->alias('u')->join('user_cancel c','c.userid=u.id')
                    ->field('u.id,u.uid')
                    ->where(['c.status' => 0,'u.zt' => 5,'c.id' => $id])
                    ->find();

                if(!$uinfo){
                    $this->error('记录状态不正确,请确认！');
                }

                $remark = empty($param['remark']) ? '审核成功' : $param['remark'];

                $this->cancel($status,$uinfo,$remark);
            }else{
                $this->error('缺少必要参数');
            }
        }
    }
    /**
     * 注销审核
     */
    private function cancel($zt,$uinfo,$remark){
        Db::startTrans();
        if($zt == 6){ //注销
            global $remodi_db;
            Db::name('user_cancel')->where(['userid' => $uinfo['id'],'status' => 0])->update([
                'operator_id' => $this->auth->id,
                'status' => 2,
                'endtime' => time(),
                'msg' => $remark,
            ]);
            //修改标识
            $uid = 'HcancelM_'.str_replace('@','~',$uinfo['uid']).'_'.$uinfo['id'];
            Db::connect($remodi_db)->name('domain_user')->where('id',$uinfo['id'])->update(['zt' => 6,'uid' => $uid]);
            $redis = new Redis();
            $key = $redis->get('login_'.$uinfo['id']);
            $redis->del($key);
        }elseif($zt == 1){ //恢复正常
            Db::name('user_cancel')->where(['userid' => $uinfo['id'],'status' => 0])->update([
                'operator_id' => $this->auth->id,
                'status' => 1,
                'endtime' => time(),
                'msg' => $remark,
            ]);
            $uid = $uinfo['uid'];
        }
        Db::name('domain_user')->where('id',$uinfo['id'])->update(['zt' => $zt,'uid' => $uid]);
        Db::commit();
        $e = new sendMail();
        $e->cancelNotice($uinfo['id'],$uinfo['uid'],$zt,$remark);

        $this->success('操作成功');
    }
}
