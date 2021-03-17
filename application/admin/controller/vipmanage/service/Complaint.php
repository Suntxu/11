<?php

namespace app\admin\controller\vipmanage\service;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 关联记录
 * @icon fa fa-user
 */
class Complaint extends Backend
{
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('user_service_complaint');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit,$special) = $this->buildparams();
            $total = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')->join('domain_user u1','l.kefuid=u1.id','left')
                          ->where($where)
                          ->count();

            $list = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')->join('domain_user u1','l.kefuid=u1.id','left')
                         ->field('u.uid,u1.uid as u1id,l.createtime,l.title,l.content')
                         ->where($where)->order($sort,$order)->limit($offset, $limit)
                         ->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['u.uid'] = $v['uid'];
                $v['u1.uid'] = $v['u1id'];
                $v['l.createtime'] = $v['createtime'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
