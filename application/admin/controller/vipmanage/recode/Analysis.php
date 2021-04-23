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
class Analysis extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_record');
    }
    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isAjax()) {

            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);
            if(empty($filter)){
                $this->error('请设置搜索条件后查询数据');
            }
            list($where, $sort, $order, $offset, $limit,$uid) = $this->buildparams();

            $def = '';
            if($uid){
                $userid = Db::name('domain_user')->where('uid',trim($uid))->value('id');
                $def = ' userid = '.($userid ? $userid : 0);
            }


            $total = $this->model->where($where)->where($def)->count();
            $list = $this->model
                         ->field('tit,RR,Type,Value,Line,Status,time')
                         ->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)
                         ->select();
            $fun = Fun::ini();
            $line=array('default'=>'默认','unicom'=>'联通','telecom'=>'电信','mobile'=>'移动','edu'=>'中国教育网',
                    'oversea'=>'境外','baidu'=>'百度','biying'=>'必应','google'=>'谷歌');
            $status=array('Enable'=>'启用','Disable'=>'停止');
            foreach($list as &$v){
                $v['Status'] = $fun->getStatus($v['Status'],$status);
                $v['Line']=$fun->getStatus($v['Line'],$line);
                $v['group'] = '';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
