<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
/**
 * 注册商实名模板发送邮件记录
 *
 * @icon fa fa-user
 */
class Regtenemail extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('zcs_emailrecord');
    }
    /**
     * 查看
     */
    public function index($ids = '')
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();

            $def = '';
            if($group){
                $apiids = $this->getApis($group);
                $def .= ' r.api_id in ('.implode(',',$apiids).')';
            }

            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                ->where($where)->where($def)->count();
            $list =  $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                         ->field('r.email,u.uid,r.time,r.api_id')
                         ->where($where)->where($def)->order($sort,$order)
                         ->limit($offset, $limit)
                         ->select();

            $apis = $this->getApis(-1);
            foreach ($list as $k => &$v) {

                $v['r.email'] = $v['email'];
                $v['u.uid'] = $v['uid'];
                $v['r.time'] = $v['time'];
                $v['group'] = $apis[$v['api_id']]['regname'];
                $v['api_id'] = $apis[$v['api_id']]['tit'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('u1.uid'));
        return $this->view->fetch();
    }

}
