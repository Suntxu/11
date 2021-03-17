<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名注册退款记录
 *
 * @icon fa fa-user
 */
class Refund extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_refund_log');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                          ->where($where)->count();
            
            //获取总金额
            $znum = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                    ->where($where)->sum('r.money');

            $list = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                    ->field('u.uid,r.tit,r.money,r.atime,r.zcs,r.api_id,r.create_time,r.oid')
                    ->where($where)->order($sort, $order)->limit($offset, $limit)
                    ->select();

            $apis = $this->getApis(-1);
            foreach($list as $k => $v){
              $list[$k]['r.create_time'] =  $v['create_time'];
              $list[$k]['api_id'] = $apis[$v['api_id']]['tit'];
              $list[$k]['zcs'] = $apis[$v['api_id']]['regname'];
              $list[$k]['num'] = $znum;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('uid'));
        return $this->view->fetch();
    }

}
