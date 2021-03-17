<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名注册记录
 *
 * @icon fa fa-user
 */
class Loginlog extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    protected $fun = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_loginlog');
    }

    /**
     * 查看
     */
    public function index($ids = '')
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('l')->join('domain_user u','l.userid = u.id')
                          ->where($where)->count();

            $list = $this->model->alias('l')->join('domain_user u','l.userid = u.id')
                         ->field('l.sj,l.uip,u.uid')
                         ->where($where)->order($sort,$order)->limit($offset, $limit)->select();
            $arr = [];
            foreach($list as $k => $v){
                $arr[$k]['u.uid'] =$v['uid'];
                $arr[$k]['l.sj'] =$v['sj'];
                $arr[$k]['l.uip'] =$v['uip'];
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('u.uid'));
        return $this->view->fetch();
    }

}
