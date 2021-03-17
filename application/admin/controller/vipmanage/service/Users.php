<?php

namespace app\admin\controller\vipmanage\service;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 客服列表
 * @icon fa fa-user
 */
class Users extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('exclusive_user');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')->join('domain_user u1','l.kfuserid = u1.id','left')
                          ->where($where)->count();

            $list = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')->join('domain_user u1','l.kfuserid = u1.id','left')
                         ->field('l.time,u.uid,u1.uid as uuid,l.type')
                         ->where($where)->order($sort,$order)->limit($offset, $limit)
                         ->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['u.uid'] = $v['uid'];
                $v['u1.uid'] = empty($v['uuid']) ? '官网' : $v['uuid'];
                $v['l.time'] = $v['time'];
                $v['l.type'] = $fun->getStatus($v['type'],['--','怀米大使默认绑定','会员中心第一次绑定','更换绑定']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('uid',$this->request->get('u1.uid'));
        return $this->view->fetch();
    }
}
