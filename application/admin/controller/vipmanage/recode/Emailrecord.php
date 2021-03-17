<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 邮箱验证码发送记录
 *
 */
class Emailrecord extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('email_sendlog');
    }
    /**
     * 查看
     */
    public function index($ids = '')
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->alias('l')->join('domain_user u','u.id = l.userid')->where($where)->count();
         
            $list = $this->model->alias('l')->join('domain_user u','u.id = l.userid')
                ->field('l.email,l.code,l.type,l.time,l.uip,u.uid')
                ->where($where)
                ->order($sort,$order)->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['l.email'] = $v['email'];
                $v['l.code'] = $v['code'];
                $v['l.time'] = $v['time'];
                $v['l.type'] = $fun->getStatus($v['type'],['找回密码','重置安全码','转回原注册商','重置手机号','账号注销']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids', $ids);
        return $this->view->fetch();
    }

}
