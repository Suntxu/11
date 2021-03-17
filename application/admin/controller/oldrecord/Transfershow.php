<?php

namespace app\admin\controller\oldrecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 任务列表
 *
 * @icon fa fa-user
 */
class Transfershow extends Backend
{
    protected $noNeedRight = ['*'];
    protected $model = null;

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
        //由于子任务同步 暂时写一个表名
        $tableName = $this->getRecordYearTableName('Task_Detail_20');

        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit,$id,$type,$zcs,$year) = $this->buildparams();

            if(empty($year)){
                $year = $tableName[0];
            }
//            if($id){
//                 //提交任务时间 小于 2019-12-31 09:30:00 读取2019的表
//                $taskTime = Db::table(PREFIX.'Task_record')->where('id',$id)->value('createtime');
//                if($taskTime < strtotime('2019-12-31 09:30:00')){
//                    $year = '_2019';
//                }else{
//                    $year = '_2019';
//                }
//            }
            if($type == 2){
                $tableName = PREFIX.'Task_Detail_'.$year;
            }else{
                $tableName = PREFIX.'Task_Detail_'.$type.'_'.$year;
            }
            $field = 'tit,TaskStatusCode,ErrorMsg,id,CreateTime,api_id';
            $zje = '无统计';
            if(empty($id)){
                $def = '1 = 1';
            }else{
                $def = 'taskid = '.$id;
            }
            if($zcs){
                $apiid = $this->getApis($zcs);
                if($apiid){
                    $def .= ' and api_id in('.implode(',',$apiid).') ';
                }
            }
            // 显示价格
            if($type == 2 || $type == 4){
                $field .= ',money';
                // 统计金额
                $sql = $this -> setWhere();

                $conm = 'SELECT sum(`money`) as n FROM '.$tableName.$sql.' and TaskStatusCode = 2 and '.$def;

                $res = $this->model->query($conm);
                $zje = $res[0]['n'];

            }elseif($type == 5){
                // 显示解析记录
                $field .= ',Value,Rr,Type';
            }
            
            $total = $this->model->table($tableName)->where($where)->where($def)->count();
            $list = $this->model->table($tableName)->field($field)->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            $oper = $fun->getStatus($type,['--','更换信息模板','注册域名','修改dns','域名续费','批量解析','批量删除解析','批量找回域名']);
            $apis = $this->getApis(-1);
            foreach($list as $k => $v){
                if(empty($v['api_id'])){
                    $list[$k]['api_id'] = '--';
                    $list[$k]['spec'] = '--';
                }else if($v['api_id'] == -1){
                    $list[$k]['api_id'] = '添加阿里云云解析';
                    $list[$k]['spec'] = '--';
                }else{
                    $list[$k]['api_id'] = $apis[$v['api_id']]['tit'];
                    $list[$k]['spec'] = $apis[$v['api_id']]['regname'];
                }
                $list[$k]['oper'] = $oper;
                $list[$k]['zje'] = $zje;
                $list[$k]['TaskStatusCode'] = $fun->getStatus($v['TaskStatusCode'],['执行中',2 => '执行成功','执行失败',9=>'已退款']);
                if(isset($v['money'])){
                    $list[$k]['rems'] = $v['money'];
                }elseif($type == 5){
                    $list[$k]['rems'] = '解析类型：'.$v['Type'].' 解析记录：'.$v['Rr'].' 解析值：'.$v['Value'];
                }else{
                    $list[$k]['rems'] = '--';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }



        $this->view->assign([
            'id' => $this->request->get('tid'),
            'type' => $this->request->get('type'),
            'tableName' => $tableName,
        ]);
        return $this->view->fetch();
    }
}
