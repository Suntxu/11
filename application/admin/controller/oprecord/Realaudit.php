<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 管理员操作 - 实名审核
 *
 * @icon fa fa-user
 */
class Realaudit extends Backend
{
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        $this->model = Db::name('user_renzheng');
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $def = '';
            if(!empty($group)){
                if((mb_strlen($group) > 5 && !strpos($group,'·')) || strpos($group,'公司')){
                    $def = ' r.busname like "%'.$group.'%" ';
                }else{
                    $x = mb_substr($group,0,1); //获取姓
                    $m = mb_substr($group,1);
                    $def = ' r.xing = "'.$x.'" ';
                    if(!empty($m)){
                        $def .= ' and r.ming like "'.$m.'%" ';
                    }
                }
            }
            
            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id')->join('admin a','a.id=r.admin_id')
                    ->where($where)->where($def)
                    ->count();

            $list = $this->model->alias('r')->join('domain_user u','r.userid=u.id')->join('admin a','a.id=r.admin_id')
                    ->field('r.id,u.uid,r.renzheng,r.status,r.createtime,r.checktime,r.remark,r.title,r.xing,r.ming,r.busname,a.nickname')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach($list as $k=>$v){
                if($v['renzheng'] == 0){
                    $list[$k]['group'] = $v['xing'].$v['ming'];
                }else{
                    $list[$k]['group'] = $v['busname'];
                }
                $list[$k]['r.renzheng'] = Fun::ini()->getStatus($v['renzheng'],['个人认证','企业认证']);
                $list[$k]['r.status'] = Fun::ini()->getStatus($v['status'],['--','失败','成功',9=>'已删除']);
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['r.title'] = $v['title'];
                $msg = json_decode($v['remark'],true);
                if($msg){
                    $list[$k]['remark'] = $msg['msg'];
                }
                $list[$k]['a.nickname'] = $v['nickname'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
       
        return $this->view->fetch();
    }
}

 



