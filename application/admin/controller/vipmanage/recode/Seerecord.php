<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名注册记录
 *
 * @icon fa fa-user
 */
class Seerecord extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    protected $fun = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_seerecord');
    }
    /**
     * 查看
     */
    public function index($ids = '')
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')
                          ->where($where)->count();

            $list = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')
                         ->field('l.tit,l.time,u.uid')
                         ->where($where)->order($sort,$order)->limit($offset, $limit)
                         ->select();
            foreach($list as $k => $v){
                $list[$k]['l.time'] = $v['time'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('u.uid'));
        return $this->view->fetch();
    }

}
