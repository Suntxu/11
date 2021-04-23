<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use think\Config;
/**
 * 任务列表
 *
 * @icon fa fa-user
 */
class Transfer extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit,$group,$special_condition,$uid) = $this->buildparams();

            $def = '1 = 1';

            if($group){
                if($group == '怀米网'){
                    $def .= ' and r.remark = "[-100]" ';
                }else{
                    $def .= ' and r.remark like "%'.addslashes($group).'%" ';
                }
            }
            if($special_condition){
                $param = json_decode($this->request->param('filter'),true);
                if(empty($param['r.tasktype']) || $param['r.tasktype'] == 2){
                    $def .= ' and r.tasktype = 2 and r.a_type = '.$special_condition;
                }else{
                    $def .= ' and  r.id = 0';
                }
            }
            if($uid){
                $uid = trim($uid);
                $userid = Db::name('domain_user')->where('uid',$uid)->value('id');
                if(empty($userid)){
                    $userid = Db::name('admin')->where('nickname',$uid)->value('id');
                    $def .= ' and r.userid = '.(empty($userid) ? 0 : -$userid);
                }else{
                    $def .= ' and r.userid = '.$userid;
                }
            }

            $total = Db::table(PREFIX.'Task_record')->alias('r')->join('domain_voucher v','r.v_id=v.id','left')
                        ->where($where)->where($def)
                        ->count();

            $list = Db::table(PREFIX.'Task_record')->alias('r')->join('domain_voucher v','r.v_id=v.id','left')
                        ->field('r.userid,v.bh,r.createtime,r.tasktype,r.status,r.dcount,r.account,r.uip,r.v_r_money,r.id,r.remark,r.a_type,r.createtime,r.ltype')
                        ->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)
                        ->select();

            if(empty($userid)){
                $userids = array_unique(array_column($list,'userid'));
                if(count($userids) == 1 && $userids[0] < 0){ //搜索负数id
                    $pinfo = Db::name('admin')->where('id',abs($userids[0]))->column('nickname','-(id)');
                }else{
                    $aids = [];
                    $uaids = [];
                    foreach($userids as $v){
                        if($v > 0){
                            $uaids[] = $v;
                        }else{
                            $aids[] = abs($v);
                        }
                    }
                    $userinfo = Db::name('domain_user')->whereIn('id',$uaids)->column('uid','id');
                    $adinfo = Db::name('admin')->whereIn('id',$aids)->column('nickname','-(id)');
                    $pinfo = $userinfo + $adinfo;
                }

            }

            //查询域名总数量
            $znum = Db::table(PREFIX.'Task_record')->alias('r')->join('domain_voucher v','r.v_id=v.id','left')
                ->where($where)->where($def)->sum('r.dcount');
            $fun = Fun::ini();
            //提交任务时间 小于 2019-12-31 09:30:00 读取2019的表
            $otime = strtotime(Config::get('task_split_date'));
            foreach($list as $k => $v){
                // 获取创建时间
                $list[$k]['r.tasktype'] = $fun->getStatus($v['tasktype'],['--','更换信息模板','注册域名','修改dns','域名续费','批量解析','批量删除解析','批量找回域名']);
                $list[$k]['r.status'] = $fun->getStatus($v['status'],['任务执行中','任务执行完成']);
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['r.userid'] = $v['userid'];
                $list[$k]['r.id'] = $v['id'];
                $list[$k]['znum'] = $znum;
                $list[$k]['group'] = $v['remark'] == '[-100]' ? '怀米网':$v['remark'];
                if($v['tasktype'] == 2){
                    $list[$k]['special_condition'] = $fun->getStatus($v['a_type'],['普通','拼团','限量','注册包']);
                }else{
                    $list[$k]['special_condition'] = '--';
                }
                $list[$k]['flag'] = ($otime > $v['createtime']) ? false : true;
                $list[$k]['r.ltype'] = $fun->getStatus($v['ltype'],['官网','分销系统']);
                if(empty($userid)){
                    $list[$k]['spec'] = isset($pinfo[$v['userid']]) ? $pinfo[$v['userid']] : '-';
                }else{
                    $list[$k]['spec'] = $uid;
                }

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('uid'));
        return $this->view->fetch();
    }

}
