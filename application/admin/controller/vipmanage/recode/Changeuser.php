<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 关键性信息修改记录
 *
 */
class Changeuser extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('changeuser_log');
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
                ->field('l.yuid,l.uid,l.uip,l.create_time,l.type,u.uid as uuid')
                ->where($where)
                ->order($sort,$order)->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['u.uid'] = $v['uuid'];
                $v['l.uid'] = $v['uid'];
                $v['l.type'] = $fun->getStatus($v['type'],['账户修改','密码修改','安全码修改','更换手机号','修改手机号(邮箱)']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids', $ids);
        return $this->view->fetch();
    }

}
