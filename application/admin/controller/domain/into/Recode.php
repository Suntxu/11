<?php

namespace app\admin\controller\domain\into;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名转回记录
 *
 * @icon fa fa-user
 */
class Recode extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index($ids = '')
    {
        //设置过滤方法
        $this ->request->filter('strip_tags');
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('batch_into')->alias('b')->join('domain_into d','d.bid = b.id')
                        ->where($where)->count();

            $list = Db::name('batch_into')->alias('b')->join('domain_into d','d.bid = b.id')
                        ->field('d.domian,b.subdate,b.finishdate,b.audit,b.bath,b.email,b.targetuser,b.special,b.reg_id')
                        ->where($where)->order($sort,$order)->limit($offset, $limit)
                        ->select();
            $arr = [];
            $fun = Fun::ini();
            $cates = $this->getCates();
            foreach($list as $k => $v){
                $arr[$k]['b.special'] = $fun->getStatus($v['special'],['普通','<sapn style="color:orange;">预释放</span>','<sapn style="color:red;">0元转回</span>']);
                $arr[$k]['d.domian'] = $v['domian'];
                $arr[$k]['b.targetuser'] = $v['targetuser'];
                $arr[$k]['b.subdate'] = $v['subdate'];
                $arr[$k]['b.finishdate'] = $v['finishdate'];
                $arr[$k]['b.email'] = $v['email'];
                $arr[$k]['b.bath'] = $v['bath'];
                $arr[$k]['b.reg_id'] = empty($cates[$v['reg_id']]) ? '--' : $cates[$v['reg_id']];;
                $arr[$k]['b.audit'] = Fun::ini()->getStatus($v['audit'],['<span style="color:orange;">等待处理</span>','<span style="color:green">审核成功</span>','<span style="color:red">审核失败</span>','<span style="color:gray;">已撤销</span>','<span style="color:red;">审核中</span>']);
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign('ids',$ids);
        return $this->view->fetch();
    }

}
