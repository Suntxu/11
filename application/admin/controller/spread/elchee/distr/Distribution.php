<?php

namespace app\admin\controller\spread\elchee\distr;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 分销系统 已经在审核完成得店铺
 *
 * @icon fa fa-user
 */
class Distribution extends Backend
{
    protected $relationSearch = false;
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
        //设置过滤方法
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('distribution_log')->alias('d')->join('domain_user u','u.id=d.userid','left')->where($where)->where('d.status in (1,2)')->count();
            $list = Db::name('distribution_log')->alias('d')->join('domain_user u','u.id=d.userid','left')
                    ->field('u.uid,d.msg,d.add_time,d.status')
                    ->where($where)->where('d.status in (1,2)')
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k =>&$v){
                $v['d.status'] = $fun->getStatus($v['status'],[1=>'审核失败','审核成功']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}


