<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Regdomain extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_user');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$year) = $this->buildparams();
                $total = $this->model
                        ->alias('u')
                        ->join('admin a','u.topspreader=a.id')
                        ->join(PREFIX.'Task_record r','r.userid=u.id')
                        ->join(PREFIX.'Task_Detail'.$year.' d','r.id=d.taskid')
                        ->where($where)
                        ->where('r.tasktype=2 and r.status=1 and d.TaskStatusCode=2 and u.topspreader != 0')
                        ->count();
                $list = $this->model
                        ->alias('u')
                        ->field('u.id,u.uid,a.nickname,d.tit,r.createtime,d.money,r.a_type')
                        ->join('admin a','u.topspreader=a.id')
                        ->join(PREFIX.'Task_record r','r.userid=u.id')
                        ->join(PREFIX.'Task_Detail'.$year.' d','r.id=d.taskid')
                        ->where($where)
                        ->where('r.tasktype=2 and r.status=1 and d.TaskStatusCode=2 and u.topspreader != 0')
                        ->order($sort, $order)
                        ->limit($offset, $limit)
                        ->select();
            $arr = [];
            $fun = Fun::ini();
            foreach($list as $k => $v){
                $arr[$k]['a.nickname'] = $v['nickname'];
                $arr[$k]['u.uid'] = $v['uid'];
                $arr[$k]['r.createtime'] = $v['createtime'];
                $arr[$k]['d.tit'] = $v['tit'];
                $arr[$k]['d.money'] = $v['money'];
                $arr[$k]['r.a_type'] = $fun->getStatus($v['a_type'],['普通','拼团','限量']);
            }
            unset($list);
            $result = array("total" => $total,"rows" => $arr);
            return json($result);
        }
        return $this->view->fetch();
    }


}


