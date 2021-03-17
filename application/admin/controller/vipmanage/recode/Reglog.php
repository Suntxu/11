<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名注册记录
 *
 * @icon fa fa-user
 */
class Reglog extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
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
        //设置过滤方法
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit,$group,$special_condition,$nuid) = $this->buildparams();
            
            $def = '';
            //大写字段搜索报错 采用自定义变量
            if($group){
                $bed = explode(' - ', $group);
                $def .= ' and d.CreateTime between '.strtotime($bed[0]).' and '.strtotime($bed[1]);
            }

            if($sort == 'group'){
                $sort = 'd.CreateTime';
            }
            if($special_condition){
                $apiids = $this->getApis($special_condition);
                $def .= ' and d.api_id in ('.($apiids ? implode(',',$apiids):0).')';
            }
            if($nuid){
                $TextAv=str_replace("\r","",$nuid);
                $Text=preg_split("/\n/",$TextAv);
                $Text = array_map('trim',array_filter($Text));
                $def .= ' and u.uid not in("'.implode('","',$Text).'")';
            }
            if($sort == 'group'){
                $sort = ' d.CreateTime ';
            }

            $filter = $this->request->param('filter');
            if($filter == '{}'){
                $total = Db::table(PREFIX.'Task_Detail')->where('TaskStatusCode',2)->count();
                // $total = $this->model->alias('r')->join(PREFIX.'Task_Detail d','r.id = d.taskid','left')
                //     ->where(['d.TaskStatusCode' => 2,'r.tasktype' => 2])
                //     ->count();
            }else{
                $total = $this->model->alias('r')->join(PREFIX.'Task_Detail'.' d','r.id = d.taskid','right')->join('domain_user u','r.userid=u.id','left')
                    ->where($where)->where(' d .TaskStatusCode = 2 and r.tasktype = 2 '.$def)
                    ->count();
            }
            $list = Db::table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail'.' d','r.id = d.taskid','right')->join('domain_user u','r.userid=u.id','left')
                ->field('r.id,r.uip,r.createtime,uid,d.tit,d.money,d.api_id,d.hz,d.CreateTime,r.a_type,r.cos_price')
                ->where($where)->where(' d .TaskStatusCode = 2 and r.tasktype = 2 '.$def)
                ->order($sort,$order)->limit($offset, $limit)
                ->select();
            $arr = [];
                // 单价总金额
            //根据条件统计总金额
            $sql = $this -> setWhere();
            $conm = 'SELECT sum(d.money) as n,sum(r.cos_price) as c FROM '.PREFIX.'Task_record as r right join '.PREFIX.'Task_Detail as d on r.id=d.taskid left join '.PREFIX.'domain_user as u on  r.userid= u.id '.$sql.' and r.tasktype = 2 AND d.TaskStatusCode=2 '.$def;

            $res = Db::query($conm);
            $apis = $this->getApis(-1);
            $fun = Fun::ini();
            foreach($list as $k => $v){
                $arr[$k]['u.uid'] = $v['uid'];
                if(empty($v['uip'])){
                    $arr[$k]['r.uip'] = '--';
                }else{
                    $arr[$k]['r.uip'] = $v['uip'];
                }
                $arr[$k]['cos_price'] = $v['cos_price'];
                $arr[$k]['d.money'] = sprintf('%.2f',$v['money']);
                $arr[$k]['group'] = $v['CreateTime'];
                $arr[$k]['r.createtime'] = $v['createtime'];
                $arr[$k]['zje'] =$res[0]['n'];
                $arr[$k]['czje'] =$res[0]['c'];
                $arr[$k]['d.tit'] =$v['tit'];
                $arr[$k]['api_id'] = $apis[$v['api_id']]['tit'];
                $arr[$k]['special_condition'] = isset($apis[$v['api_id']]) ? $apis[$v['api_id']]['regname'] : '-';
                $arr[$k]['d.hz'] =$v['hz'];
                $arr[$k]['r.a_type'] = $fun->getStatus($v['a_type'],['普通','拼团','限量','注册包']);
                $arr[$k]['special_status'] = $v['uid'];
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $param = $this->request->param(); 
        //限量优惠注册 后缀
        $h_id = isset($param['h_id']) ? $param['h_id'] : '';
        if($h_id){
            $hids = Db::name('domain_limit_order')->where('hid',$h_id)->column('id');
            $h_id = implode(',',$hids);
        }
        $this->view->assign([
            'uid' => isset($param['u_uid']) ? $param['u_uid'] : '',
            'aid' => isset($param['a_id']) ? $param['a_id'] : '',
            'a_type' => isset($param['a_type']) ? $param['a_type'] : '',
            'l_id' => isset($param['l_id']) ? $param['l_id'] : '',
            'h_id' => $h_id,
        ]);
        return $this->view->fetch();
    }

}
