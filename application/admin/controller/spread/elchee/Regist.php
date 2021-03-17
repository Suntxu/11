<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名注册 版本二 根据子任务获取佣金
 *
 * @icon fa fa-user
 */
class Regist extends Backend
{

    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::table(PREFIX.'Task_record');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group,$special_condition,$year) = $this->buildparams();
            $def = 'r.tasktype= 2 and r.status = 1 ';
            if(empty($group)){
                $spcel = '';
            }elseif($group == 2){
                $spcel = ' and r.yj > 0 ';
            }else{
                $spcel = ' and r.yj <= 0 ';
            }

            if($special_condition){
                $apiids = $this->getApis($special_condition);
                $def .= ' and api_id in ('.implode(',',$apiids).')';
            }
            $total = $this->model->alias('r')->join(PREFIX.'Task_Detail'.$year.' d','d.taskid=r.id and  d.TaskStatusCode = 2')->join('domain_user u','r.userid=u.id')
                    ->join('domain_user u1','r.tuserid=u1.id')
                    ->where($where)->where($def.$spcel)
                    ->count();
            $list = Db::table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail'.$year.' d','d.taskid=r.id and  d.TaskStatusCode = 2 ')->join('domain_user u','r.userid=u.id')
                    ->join('domain_user u1','r.tuserid=u1.id')
                    ->where($where)->where($def.$spcel)
                    ->field('d.money,d.tit,r.createtime,u.uid,u1.uid as uuid,r.yj,d.hz,d.api_id,r.a_type')
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
                //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT  sum(IF(yj>0,d.money,0)) as n,sum(IF(yj<=0,d.money,0)) as n1 FROM '.PREFIX.'Task_Detail'.$year.' d inner join '.PREFIX.'Task_record r on d.taskid=r.id  and  d.TaskStatusCode = 2 inner join '.PREFIX.'domain_user u1 on r.tuserid=u1.id where '.$def.$spcel;
            }else{
                $conm = 'SELECT sum(IF(yj>0,d.money,0)) as n,sum(IF(yj<=0,d.money,0)) as n1 FROM '.PREFIX.'Task_Detail'.$year.' d inner JOIN '.PREFIX.'Task_record r ON d.taskid = r.id  and  d.TaskStatusCode = 2 inner JOIN '.PREFIX.'domain_user u ON r.userid = u.id INNER JOIN '.PREFIX.'domain_user u1 ON r.tuserid = u1.id '.$sql.' AND '.$def.$spcel;
            }
            $res = Db::query($conm);
            $zjef = empty($res[0]['n']) ? 0 : $res[0]['n'];
            $zjey = empty($res[0]['n1']) ? 0 : $res[0]['n1'];
            $apis = $this->getApis(-1);
            $fun = Fun::ini();
            foreach($list as $k => $v){
               $list[$k]['zje'] = $zjef;
               $list[$k]['zje1'] = $zjey;
               $list[$k]['d.money'] = $v['money'];
               $list[$k]['d.tit'] = $v['tit'];
               $list[$k]['r.createtime'] = date('Y-m-d H:i:s',$v['createtime']);
               $list[$k]['api_id'] =$apis[$v['api_id']]['tit'];
               $list[$k]['special_condition'] = $apis[$v['api_id']]['regname'];
               $list[$k]['d.hz'] = $v['hz'];
               $list[$k]['group'] = $v['yj'] > 0 ? '<span style="color:green">已记录</span>':'<span style="color:red">未记录</span>';
               $list[$k]['r.a_type'] = $fun->getStatus($v['a_type'],['普通','拼团','限量']);
               // 实付金额
               // $list[$k]['sfzje'] = $res[0]['s'];
               $list[$k]['u.uid'] = $v['uid'];
               $list[$k]['u1.uid'] = $v['uuid'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

}


