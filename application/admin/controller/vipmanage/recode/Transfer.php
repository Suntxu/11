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
            list($where, $sort, $order, $offset, $limit,$group,$special_condition) = $this->buildparams();
            if(empty($group)){
                $def = '';
            }elseif($group == '怀米网'){
                $def = ' r.remark = "[-100]" ';
            }else{
                $def = ' r.remark like "%'.addslashes($group).'%" ';
            }
           
            if($special_condition !== '' ){
                $param = json_decode($this->request->param('filter'),true);
                if(empty($param['r.tasktype']) || $param['r.tasktype'] == 2){
                    $def .= (empty($def) ? '  ' : ' and ').' r.tasktype = 2 and  r.a_type = '.$special_condition;
                }else{
                    $def .= (empty($def) ? '  ' : ' and ').' r.id = 0 ';
                    
                }
            }

            $total = Db::table(PREFIX.'Task_record')->alias('r')->join('domain_user u','r.userid=u.id','left')->join('domain_voucher v','r.v_id=v.id','left')
                        ->where($where)->where($def)->count();
            $list = Db::table(PREFIX.'Task_record')->alias('r')->join('domain_user u','r.userid=u.id','left')->join('domain_voucher v','r.v_id=v.id','left')
                        ->field('u.id as userid,u.uid,v.bh,r.createtime,r.tasktype,r.status,r.dcount,r.account,r.uip,r.v_r_money,r.id,r.remark,r.a_type,r.createtime,r.ltype')
                        ->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)->select();

            //查询域名总数量
            $znum = Db::table(PREFIX.'Task_record')->alias('r')->join('domain_user u','r.userid=u.id','left')->join('domain_voucher v','r.v_id=v.id','left')
                ->where($where)->where($def)->sum('r.dcount');
            $fun = Fun::ini();
            //提交任务时间 小于 2019-12-31 09:30:00 读取2019的表
            $otime = strtotime(Config::get('task_split_date'));
            foreach($list as $k => $v){
                // 获取创建时间
                $list[$k]['r.tasktype'] = $fun->getStatus($v['tasktype'],['--','更换信息模板','注册域名','修改dns','域名续费','批量解析','批量删除解析','批量找回域名']);
                $list[$k]['r.status'] = $fun->getStatus($v['status'],['任务执行中','任务执行完成']);
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['u.id'] = $v['userid'];
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
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('uid'));
        return $this->view->fetch();
    }

}
