<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;

/**
 * IP白名单设置
 *
 */
class Whiteiplist extends Backend
{
    public function index(){
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total =  Db::name('domain_user_white_ip_list')->alias('u')->join('domain_user du','u.user_id=du.id')->where($where)->count();
            $list = Db::name('domain_user_white_ip_list')
                ->alias('u')
                ->join('domain_user du','u.user_id=du.id')
                ->field('add_ip,white_ip,uid,status,add_time,update_time')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k => $v){
                $list[$k]['du.uid'] = $v['uid'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}