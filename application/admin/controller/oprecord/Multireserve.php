<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use think\Config;

/**
 * 管理员操作 -多通道预定记录
 *
 * @icon fa fa-user
 */
class Multireserve extends Backend
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
            list($where, $sort, $order, $offset, $limit,$zcs) = $this->buildparams();
            
            $def = '';
            if($zcs){
                $regid = Config::get('mult_domain_reserve_zcs_id');
                $tinfo = $regid[$zcs];
                $tinfo = array_keys($tinfo['api']);
                $def = ' r.api_id = '.$tinfo[0];
            }

            $total = Db::name('domain_multi_reserve_record')->alias('r')->join('admin a','a.id=r.admin_id')
                    ->where($where)->where($def)
                    ->count();

            $list = Db::name('domain_multi_reserve_record')->alias('r')->join('admin a','a.id=r.admin_id')
                    ->field('r.tit,r.del_time,r.api_id,r.create_time,r.status,a.nickname')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $fun = Fun::ini();
            $apis = $this->getApis(-1);

            foreach($list as $k => $v){

               $list[$k]['r.tit'] = $v['tit'];
               $list[$k]['r.create_time'] = $v['create_time'];
               $list[$k]['r.status'] = $fun->getStatus($v['status'],['','提交成功','预定成功','预定失败','提交失败']);
               $list[$k]['a.nickname'] = $v['nickname'];
               $list[$k]['group'] = empty($apis[$v['api_id']]) ? '' : $apis[$v['api_id']]['regname'].'——'.$apis[$v['api_id']]['tit'];

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
       
        return $this->view->fetch();
    }
}

 



