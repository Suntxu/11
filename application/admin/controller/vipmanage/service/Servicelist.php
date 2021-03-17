<?php

namespace app\admin\controller\vipmanage\service;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 客服列表
 * @icon fa fa-user
 */
class Servicelist extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('user_service');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')
                          ->where($where)->where('u.special',1)->count();
            
            $list = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')
                         ->field('l.wx,l.qq,l.nickname,l.tel,l.createtime,l.img,u.uid,l.online,l.sex')
                         ->where($where)->where('u.special',1)->order($sort,$order)->limit($offset, $limit)
                         ->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['img'] = '/uploads'.$v['img'];
                $v['online'] = $fun->getStatus($v['online'],['<span style="color:red">不在线</span>','<span style="color:green">在线</span>']);
                $v['l.sex'] = $fun->getStatus($v['sex'],['女','男']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('uid',$this->request->get('uid'));
        return $this->view->fetch();
    }
}
