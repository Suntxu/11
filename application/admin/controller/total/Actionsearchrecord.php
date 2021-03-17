<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 搜索记录
 *
 * @icon fa fa-user
 */
class Actionsearchrecord  extends Backend
{

    protected $model = null;
    
    public function _initialize()
    {
        global $remodi_db;    

        parent::_initialize();
        $this->model = Db::connect($remodi_db)->name('page_search_record');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->group('type')->count();

            $now = strtotime('now');
            //今日数量
            $field = 'count(case when create_time between '.strtotime(date('Y-m-d')).' and '.$now.' then 1 end) as jcount,';

            //本周
            $field .= 'count(case when create_time between '.strtotime('-'.date('w').' day '.date('Y-m-d')).' and '.$now.' then 1 end) as wcount,';

            //本月
            $field .= 'count(case when create_time between '.strtotime('-'.date('j').' day '.date('Y-m-d')).' and '.$now.' then 1 end) as mcount';

            $list = $this->model->field('type,count(*) as zcount,'.$field)->where($where)->group('type')->select(); 

            $fun = Fun::ini();

            foreach($list as &$v){
                $v['stype'] = $v['type'];
                $v['type'] = $fun->getStatus($v['type'],['域名简介','域名','店铺名称','店铺QQ']);
            }   

            $result = array("total" => $total, "rows" => $list);
            
            return json($result);
        }

        return $this->view->fetch();
    }


    /**
     * 详情
     */
    public function details(){

        if ($this->request->isAjax()) {   
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            // ->join('domain_user u','r.userid = u.id','left')
            $total = $this->model->alias('r')
                        ->where($where)
                        ->count();
            // ->join('domain_user u','r.userid = u.id','left')
            $list = $this->model->alias('r')
                        ->field('r.type,r.ip,r.create_time,r.data,r.total,r.userid')
                        ->where($where)->order($sort,$order)
                        ->limit($offset, $limit)
                        ->select();

            $fun = Fun::ini();

            foreach($list as &$v){
                
                $v['r.type'] = $fun->getStatus($v['type'],['域名简介','域名','店铺名称','店铺QQ']);
                
                $v['r.create_time'] = $v['create_time'];

                $v['r.userid'] = $v['userid'];

                if(mb_strlen($v['data']) > 15){
                    $v['r.data'] = $fun->returntitdian($v['data'],15).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['data'].'\')" >查看更多</span>'; 
                }else{
                    $v['r.data'] = $v['data'];
                }
            }   
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        return $this->view->fetch();



    }

}
