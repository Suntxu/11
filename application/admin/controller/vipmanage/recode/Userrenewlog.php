<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 用户续费记录
 */
class Userrenewlog extends Backend
{
    protected $noNeedRight = ['*'];
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        global $remodi_db;
        $this->model = Db::connect($remodi_db);
        parent::_initialize();
    }

    public function index($ids = '')
    {
        $tableName = $this->getRecordYearTableName('Task_Detail_4_20');
        if ($this->request->isAjax()) {
            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);
            if(empty($filter['d.tit']) ){
                $this->error('请设置域名搜索条件域名后查询数据');
            }
            list($where, $sort, $order, $offset, $limit,$spre,$special_condition,$year) = $this->buildparams();
            $year = empty($year) ? '_'.$tableName[0] : '_'.$year;
            $condition = '';
            if($spre){
                $condition = " and d.tit like '%{$spre}' ";
            }

            if($special_condition){
                $apiids = $this->getApis($special_condition);
                $condition .= ' and d.api_id in ('.implode(',',$apiids).')';
            }

            $total = $this->model->table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail_4'.$year.' d','r.id = d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                ->where($where)->where('r.tasktype = 4 '.$condition)
                ->count();
            $list = $this->model->table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail_4'.$year.' d','r.id = d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                ->field('r.id,r.status,d.ErrorMsg,r.uip,r.createtime,uid,d.tit,d.money,d.api_id,d.TaskStatusCode,d.CreateTime')
                ->where($where)->where('r.tasktype = 4 '.$condition)
                ->order($sort,$order)->limit($offset, $limit)
                ->select();
            $arr = [];
            // 单价总金额
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if($condition){
                $sql .=  $condition;
            }
            $conm = 'SELECT sum(d.money) as n FROM '.PREFIX.'Task_record as r left join '.PREFIX.'Task_Detail_4'.$year.' as d on r.id=d.taskid left join '.PREFIX.'domain_user as u on  r.userid= u.id '.$sql.' and r.tasktype = 4 AND d.TaskStatusCode=2 ';

            $res = $this->model->query($conm);
            $apis = $this->getApis(-1);
            $fun = Fun::ini();
            foreach($list as $k => $v){
                $arr[$k]['u.uid'] =$v['uid'];
                if(empty($v['uip'])){
                    $arr[$k]['r.uip'] = '--';
                }else{
                    $arr[$k]['r.uip'] = $v['uip'];
                }
                $arr[$k]['d.CreateTime'] = $v['CreateTime'];
                $arr[$k]['d.tit'] = $v['tit'];
                $arr[$k]['r.status'] = $fun->getStatus($v['status'],['<span style="color: red;">执行中</span>','<span style="color: green;">已完成</span>']);
                $arr[$k]['d.ErrorMsg'] = $v['ErrorMsg'];
                $arr[$k]['d.CreateTime'] = $v['CreateTime'];
                $arr[$k]['d.money'] = sprintf('%.2f',$v['money']);
                $arr[$k]['r.createtime'] = $v['createtime'];
                $arr[$k]['zje'] =$res[0]['n'];
                $arr[$k]['d.tit'] =$v['tit'];
                $arr[$k]['id'] =$v['id'];
                $arr[$k]['d.TaskStatusCode'] = $v['TaskStatusCode'];
                if (!empty($v['api_id'])){
                    $arr[$k]['api_id'] = $apis[$v['api_id']]['tit'];
                    $arr[$k]['special_condition'] = $apis[$v['api_id']]['regname'];
                }
            }

            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign([
            'ids' => $ids,
            'tableName' => $tableName,
        ]);
        return $this->view->fetch();
    }
}