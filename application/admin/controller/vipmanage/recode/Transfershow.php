<?php

namespace app\admin\controller\vipmanage\recode;

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

    /**
     * @var \app\admin\model\HZ
     */
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

            list($where, $sort, $order, $offset, $limit,$id,$type,$zcs) = $this->buildparams();

            if($type == 2){
                $tableName = PREFIX.'Task_Detail';
            }else{
                $tableName = PREFIX.'Task_Detail_'.$type;
            }
            $field = 'tit,TaskStatusCode,ErrorMsg,id,CreateTime,api_id';
            $zje = '无统计';
            if(empty($id)){
                $def = '1 = 1';
//                $this->error('缺少任务id');
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
                $res = Db::query($conm);
                $zje = $res[0]['n'];

            }elseif($type == 5){
                // 显示解析记录
                $field .= ',Value,Rr,Type';
            }
            $total = Db::table($tableName)->where($where)->where($def)->count();
            $list = Db::table($tableName)->field($field)->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)->select();
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
                // 获取创建时间
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
        $param = $this->request->get();
        $this->view->assign([
            'id' => empty($param['tid']) ? '' : $param['tid'],
            'type' => empty($param['type']) ? '' : $param['type'],
            'tit' => empty($param['tit']) ? '' : $param['tit'],
        ]);
        return $this->view->fetch();
    }
}
