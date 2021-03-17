<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 管理员操作 -佣金提现记录
 *
 * @icon fa fa-user
 */
class Withdraw extends Backend
{

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
            $total = Db::name('domain_promation_reward_log')->alias('l')->join('domain_user u','l.userid=u.id')->join('admin a','a.id=l.admin_id')
                    ->where($where)->where('l.status != 0')
                    ->count();
            $list = Db::name('domain_promation_reward_log')->alias('l')->join('domain_user u','l.userid=u.id')->join('admin a','a.id=l.admin_id')
                    ->field('l.id,u.uid,l.money,l.status,l.cids,l.ctime,a.nickname')
                    ->where($where)->where('l.status != 0')
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $fun = Fun::ini();
            foreach($list as $k => $v){
               $list[$k]['on'] = '查看';
               $list[$k]['money'] = $v['money']/100;
               $list[$k]['l.ctime'] = $v['ctime'];
               $list[$k]['l.status'] = $fun->getStatus($v['status'],['等待审核','提取成功','提取失败']);
               $list[$k]['a.nickname'] = $v['nickname'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
       
        return $this->view->fetch();
    }
}

 



