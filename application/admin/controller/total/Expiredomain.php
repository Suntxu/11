<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
use think\Config;
/**
 * 过期域名信息
 *
 */
class Expiredomain extends Backend
{

  
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
            //默认不显示
            $param = $this->request->get('filter');
            if($param == '{}'){
                return array("total" => 0, "rows" => []);
            }
            set_time_limit(0);

            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();

            //获取自己的用户ID
            $selfUserId = Db::name('domain_user')->whereIn('uid',Config::get('self_username'))->column('id') ;
            $def = '';
            if($group){
                $userid = Db::name('domain_user')->where('id','not in',$selfUserId)->where('uid',trim($group))->value('id');
                if(empty($userid)){
                    return array("total" => 0, "rows" => []);
                }
                $def = 'userid = '.$userid;
            }

            $total = Db::name('domain_pro_n')->where($def)->where($where)->where('userid','not in',$selfUserId)->group('hz,userid')->count();
            $list = Db::name('domain_pro_n')
                    ->field('userid,hz,dqsj,count(*) as num')
                    ->where($def)->where($where)->where('userid','not in',$selfUserId)
                    ->order($sort,$order)->limit($offset, $limit)
                    ->group('hz,userid')
                    ->select();

            foreach($list as &$v){
                $userInfo = Db::name('domain_user')->field('uid,mot')->where('id',$v['userid'])->find();
                $v['mot'] = $userInfo['mot'];
                $v['group'] = $userInfo['uid'];

            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
