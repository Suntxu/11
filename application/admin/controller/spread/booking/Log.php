<?php

namespace app\admin\controller\spread\booking;

use app\common\controller\Backend;
use think\Db;

class Log extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('assemble_team_log');
    }
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $what   =    Db::name('assemble_team_log')->alias('a')->join('assemble_team b','a.tid=b.id','left')->join('domain_user c','c.id = a.uid','left')->field('a.*,b.team_no,c.uid as name')->order($sort, $order)->where($where)->limit($offset, $limit)->select();
            $total   =    Db::name('assemble_team_log')->alias('a')->join('assemble_team b','a.tid=b.id','left')->join('domain_user c','c.id = a.uid','left')->field('a.*,b.team_no,c.uid as name')->order($sort, $order)->where($where)->count();
            foreach ($what as $key=>$value)
            {
                $what[$key]['a.log']    =   $value['log'];
                $what[$key]['b.team_no']    =   $value['team_no'];
                $what[$key]['a.id']    =   $value['id'];
                $what[$key]['a.type']    =   $value['type'];
                $what[$key]['a.admin_id']    =   $value['admin_id'];
                $what[$key]['a.admin_name']    =   $value['admin_name'];
                $what[$key]['a.created_at']    =   $value['created_at'];
                $what[$key]['c.name']    =   $value['name'];
                if (empty($value['team_no']))
                {
                    $what[$key]['b.team_no']    =  " ";
                }
                if (!empty($value['name']))
                {
                    $what[$key]['c.name']    =    "管理员操作";
                }
            }
            $result = array("total" => $total,"rows" => $what);
            return  json($result);
        }
        return $this->view->fetch();
    }
}