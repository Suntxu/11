<?php

namespace app\admin\controller\activity\disuffix;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 注册后缀优惠订单
 *
 * @icon fa fa-user
 */
class Discountslog extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_limit_order');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {  

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->alias('o')->join('domain_limit_meal m','o.lid=m.id')->join('domain_limit_houzhui h','o.hid=h.id')->join('domain_user u','o.uid=u.id')
                ->where($where)
                ->count();

            $list = $this->model->alias('o')->join('domain_limit_meal m','o.lid=m.id')->join('domain_limit_houzhui h','o.hid=h.id')->join('domain_user u','o.uid=u.id')
                ->field('m.title,h.name,o.claim_num,o.reg_num,o.price,o.colony,o.status,o.created_at,o.updated_at,o.lid,u.uid,o.id')
                ->where($where)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();

            $coun = $this->model->alias('o')->join('domain_limit_meal m','o.lid=m.id')->join('domain_limit_houzhui h','o.hid=h.id')->join('domain_user u','o.uid=u.id')
                ->field('sum(o.claim_num) as cnum,sum(o.reg_num) as rnum')
                ->where($where)
                ->find();
            $fun = Fun::ini();
            $apis = $this->getApis();
            foreach($list as &$v){
                $v['o.status'] = $fun->getStatus($v['status'],['未使用','冻结中','已用完','-1' => '已过期']);
                $v['o.colony'] = $fun->getStatus($v['colony'],['--','新用户','老用户']);
                $v['o.created_at'] = $v['created_at'];
                $v['o.lid'] = $v['lid'];
                $v['cnum'] = $coun['cnum'];
                $v['rnum'] = $coun['rnum'];
                $v['u.uid'] = $v['uid'];
                $v['show'] = '查看';
                // $v['h.aid'] = empty($v['aid']) ? '--' : $apis[$v['aid']];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
