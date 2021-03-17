<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名续费记录
 *
 * @icon fa fa-user
 */
class Renewlog extends Backend
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
    public function index($ids = '')
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit,$spre,$special_condition) = $this->buildparams();
            $condition = '';
            if($spre){
                $condition = " and d.tit like '%{$spre}' ";
            }
            if($special_condition){
                $apiids = $this->getApis($special_condition);
                $condition .= ' and d.api_id in ('.implode(',',$apiids).')';
            }
            $total = $this->model->alias('r')->join(PREFIX.'Task_Detail_4 d','r.id = d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                ->where($where)->where(' d .TaskStatusCode = 2 and r.tasktype = 4 '.$condition)
                ->count();
            $list = Db::table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail_4 d','r.id = d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                ->field('r.id,r.uip,r.createtime,uid,d.tit,d.money,d.api_id')
                ->where($where)->where(' d .TaskStatusCode = 2 and r.tasktype = 4 '.$condition)
                ->order($sort,$order)->limit($offset, $limit)
                ->select();
            $arr = [];
            // 单价总金额
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if($condition){
                $sql .=  $condition;
            }
            $conm = 'SELECT sum(d.money) as n FROM '.PREFIX.'Task_record as r left join '.PREFIX.'Task_Detail_4 as d on r.id=d.taskid left join '.PREFIX.'domain_user as u on  r.userid= u.id '.$sql.' and r.tasktype = 4 AND d.TaskStatusCode=2 ';
           
            $res = Db::query($conm);
            $apis = $this->getApis(-1);
            foreach($list as $k => $v){
                $arr[$k]['u.uid'] =$v['uid'];
                if(empty($v['uip'])){
                    $arr[$k]['r.uip'] = '--';
                }else{
                    $arr[$k]['r.uip'] = $v['uip'];
                }
                $arr[$k]['d.money'] = sprintf('%.2f',$v['money']);
                $arr[$k]['r.createtime'] = $v['createtime'];
                $arr[$k]['zje'] =$res[0]['n'];
                $arr[$k]['d.tit'] =$v['tit'];
                $arr[$k]['api_id'] = $apis[$v['api_id']]['tit'];
                $arr[$k]['special_condition'] = $apis[$v['api_id']]['regname'];
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign('ids',$ids);
        return $this->view->fetch();
    }

}
