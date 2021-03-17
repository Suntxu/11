<?php

namespace app\admin\controller\oldrecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 35互联过户记录
 *
 * @icon fa fa-user
 */
class Ownership extends Backend
{
    protected $noNeedRight = ['*'];
    protected $model = null;
    /**
     * 初始化
     */
    public function _initialize()
    {
    	global $remodi_db;
    	$this->model = Db::connect($remodi_db);
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
    	$tableName = $this->getRecordYearTableName('Task_Detail_1');

        if ($this->request->isAjax()) {   

            $def = ' r.tasktype = 1 and d.api_id = 30 ';

            list($where, $sort, $order, $offset, $limit,$year) = $this->buildparams();

            $year = empty($year) ? '_'.$tableName[0] : '_'.$year;

            $total = $this->model->table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail_1'.$year.' d','r.id=d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                    ->where($def)->where($where)
                    ->count('DISTINCT r.id');

            $list = $this->model->table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail_1'.$year.' d','r.id=d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                        ->field('u.uid,r.createtime,r.remark,r.id,r.status,r.uip,count(if(d.TaskStatusCode = 0,1,null)) as total,count(*) as bbs')
                        ->where($def)->where($where)
                        ->order($sort,$order)->limit($offset, $limit)
                        ->group('r.id')
                        ->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
               
                $conm = 'SELECT count(if(TaskStatusCode=0,1,null)) as n, count(*) as zn FROM '.PREFIX.'Task_Detail_1'.$year.' WHERE api_id = 30 ';
            }else{
                $conm = 'SELECT count(if(d.TaskStatusCode=0,1,null)) as n, count(*) as zn FROM '.PREFIX.'Task_Detail_1'.$year.' d LEFT JOIN '.PREFIX.'Task_record as r ON d.taskid = r.id  LEFT JOIN '.PREFIX.'domain_user as u ON r.userid=u.id '.$sql.' and d.api_id = 30 and d.TaskStatusCode = 0';
            }
            $qi = $this->model->query($conm);
            $fun = Fun::ini();

            //获取已处理当未结束任务的值
            foreach($list as $k => &$v){
                $v['r.createtime'] = $v['createtime'];
                $v['r.id'] = $v['id'];
               
                $v['temp'] = '查看';
               
                $v['total'] = '<font color="red">'.$v['total'].'</font>/<font color="green">'.$v['bbs'].'</font>';

                $v['num'] = '<font color="red">'.$qi[0]['n'].'</font>/<font color="green">'.$qi[0]['zn'].'</font>';
                $v['year'] = $year;
                
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        
        $this->view->assign('tableName',$tableName);
        return $this->view->fetch();
    }

    /**
     * 查询域名详情
     */
    public function show(){

        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit,$year) = $this->buildparams();

            $total = $this->model->table(PREFIX.'Task_Detail_1'.$year)->where($where)->where('api_id',30)->count();

            $list = $this->model->table(PREFIX.'Task_Detail_1'.$year)
                        ->field('tit,TaskStatusCode,ErrorMsg,CreateTime,id')
                        ->where($where)->where('api_id',30)
                        ->order($sort,$order)->limit($offset, $limit)
                        ->select();

            $fun = Fun::ini();
            foreach($list as $k => &$v){
                $v['TaskStatusCode'] = $fun->getStatus($v['TaskStatusCode'],['执行中',2 => '执行成功','执行失败']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $param = $this->request->get();
        $this->assign([
        	'ids' => empty($param['id']) ? 0 : $param['id'],
        	'year' => empty($param['year']) ? 2019 : $param['year'],
        ]);
        return $this->view->fetch();
    }


}
