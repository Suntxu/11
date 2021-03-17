<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 红包使记录
 *
 * @icon fa fa-user
 */
class Ordershow extends Backend
{

    protected $relationSearch = false;
    protected $noNeedRight = ['index'];
    /**
     * User模型对象
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
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('domain_car')->where($where)->count();
            $list = Db::name('domain_car')
                    ->field('orderid,tit,paytime,money')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('bc',$this->request->get('bc'));
        return $this->view->fetch();
    }

}


