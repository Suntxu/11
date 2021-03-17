<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名注册记录
 *
 * @icon fa fa-user
 */
class Shift extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_shift_record');
    }
    /**
     * 查看
     */
    public function index($ids = '')
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('r')->join('domain_user u','r.targetid=u.id','left')->join('domain_user u1','r.userid=u1.id','left')
                          ->where($where)->count();
            $list = $this->model->alias('r')->join('domain_user u','r.targetid=u.id','left')->join('domain_user u1','r.userid=u1.id','left')
                         ->field('r.remark,r.tit,r.createtime,r.uip,r.api_id,u.uid,u1.uid as uuid')
                         ->where($where)->order($sort,$order)
                         ->limit($offset, $limit)
                         ->select();
            $apis = $this->getApis(-1);
            foreach ($list as $k => &$v) {
                $v['u.uid'] = $v['uid'];
                $v['u1.uid'] = $v['uuid'];
                $v['r.createtime'] = $v['createtime'];
                $v['r.tit'] = $v['tit'];
                $v['api_id'] = $apis[$v['api_id']]['tit'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('u1.uid'));
        return $this->view->fetch();
    }

}
