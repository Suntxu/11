<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 管理员操作 -禁用用户
 *
 * @icon fa fa-user
 */
class Disuser extends Backend
{
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        $this->model = Db::name('user_disable_record');
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('d')->join('domain_user u','d.userid=u.id')->join('admin a','d.admin_id = a.id')
                        ->where($where)
                        ->count();
            
            $list = $this->model->alias('d')->join('domain_user u','d.userid=u.id')->join('admin a','d.admin_id = a.id')
                        ->field('d.type,d.remark,d.create_time,d.dis_days,u.uid,a.nickname,d.dis_time')
                        ->where($where)->order($sort,$order)->limit($offset, $limit)
                        ->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['u.uid'] = $v['uid'];
                $v['d.create_time'] = $v['create_time'];
                $v['d.type'] = $fun->getStatus($v['type'],['正常','<span style="color:red;">禁用</span>']);
                $v['a.nickname'] = $v['nickname'];
                if(mb_strlen($v['remark']) > 20 ){
                    $v['remark'] = $fun->returntitdian($v['remark'],20).' <span style="cursor: pointer;color: #3c8dbc;" onclick="showRemark(\''.$v['remark'].'\')">查看</span>';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
       
        return $this->view->fetch();
    }
}

 



