<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 操作员记录--注销用户
 *
 * @icon fa fa-user
 */
class Canceluser extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('user_cancel');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit,$uid) = $this->buildparams();
            $def = 'c.status != 0 ';
            if($uid){
            	$def .= ' and u.uid like "%'.str_replace('@','~',trim($uid)).'%" ';
            }

            $total = $this->model->alias('c')->join('admin a','a.id=c.operator_id')->join('domain_user u','u.id = c.userid and u.zt = 6')
            		->where($def)->where($where)
            		->count();

            $list = $this->model->alias('c')->join('admin a','a.id=c.operator_id')->join('domain_user u','u.id = c.userid and u.zt = 6')
                    ->field('c.time,c.endtime,c.status,c.endtime,c.msg,c.ip,a.nickname,c.userid,u.uid')
                    ->where($def)->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();

            foreach($list as &$v){
            	$uids = explode('_',$v['uid']);
            	$v['group'] = isset($uids[1]) ? str_replace('~','@',$uids[1]) : '--';
            	$v['c.time'] = $v['time'];
            	$v['c.endtime'] = $v['endtime'];
            	$v['c.status'] = $fun->getStatus($v['status'],['--','<span style="color: red;">注销失败</span>','<span style="color: green;">注销成功</span>']);
                $v['a.nickname'] = $v['nickname'];
                if(mb_strlen($v['msg']) > 10){
                    $v['msg'] = $fun->returntitdian($v['msg'],10).' <a href="javascript:showRemark(\''.$v['msg'].'\');">查看</a>';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


}
