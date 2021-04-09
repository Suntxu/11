<?php

namespace app\admin\controller\domain\violation;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 自查违规域名上报管理列表
 */
class Inspection extends Backend
{
    private $connect = null;

    public function _initialize()
    {
        global $violation_db;
        $this->connect = Db::connect($violation_db);
        parent::_initialize();
    }
    /**
     * 查看 自查列表
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total =  $this->connect->name('domain_violation_oneself')->where($where)->count();

            $list = $this->connect->name('domain_violation_oneself')
                    ->field('id,tit,uid,type,cause,create_time,is_redirect,registrar,img_path')
                    ->where($where)
                    ->order($sort, $order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();
            $cats = $this->getCates();
            foreach($list as &$v){
                $v['registrar'] = isset($cats[$v['registrar']]) ? $cats[$v['registrar']] : '';
                $types = explode(',',$v['type']);
                $type = '';
                foreach($types as $vv){
                    $type .= ' '.$fun->getStatus($vv,['','百度敏感词','综合敏感词','暴恐','反动','民生','色情','贪腐','其他','百度过滤词']);
                }
                if(mb_strlen($type) > 7){
                    $v['type'] = $fun->returntitdian($type,7).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$type.'\')" >查看更多</span>';
                }else{
                    $v['type'] = $type;
                }
                $v['is_redirect'] = $fun->getStatus($v['is_redirect'],['否','是']);
                if(mb_strlen($v['cause']) > 7){
                    $v['cause'] = $fun->returntitdian($v['cause'],7).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['cause'].'\')" >查看更多</span>';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 取消 自查列表
     */
    public function del($ids=null){

        if($this->request->isAjax()){

            $this->connect->name('domain_violation_oneself')->whereIn('id',$ids)->delete();

            $this->success('删除成功');

        }

    }


}
