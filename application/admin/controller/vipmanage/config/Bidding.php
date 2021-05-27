<?php

namespace app\admin\controller\vipmanage\config;

use app\common\controller\Backend;
use think\Db;
//use app\admin\common\Fun;
/**
 * 竞拍返点设置记录
 */
class Bidding extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model= Db::name('domain_prereg_reba_config');
    }
    
    /**
     * 查看 
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->alias('c')->join('domain_user u','c.userid=u.id')
                        ->where($where)
                        ->count();
            
            $list = $this->model->alias('c')->join('domain_user u','c.userid=u.id')
                        ->field('c.inner_1,c.inner_2,c.pre_66,c.pre_1000,u.uid')
                        ->where($where)
                        ->order($sort,$order)->limit($offset, $limit)
                        ->select();
            
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


}
