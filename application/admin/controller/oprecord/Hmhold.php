<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 操作员记录--怀米hold
 */
class Hmhold extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        global $remodi_db;
        parent::_initialize();
        $this->model = Db::connect($remodi_db)->name('domain_hold_record');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit,$uid,$nickname) = $this->buildparams();
            $def = '1 = 1 ';
            if($uid){
                $uid = trim($uid);
                $userid = Db::name('domain_user')->where('uid',$uid)->value('id');
                $def .= ' and userid = '.(empty($userid) ? 0 : $userid);
            }
            if($nickname){
                $nickname = trim($nickname);
                $aid = Db::name('admin')->where('nickname',$nickname)->value('id');
                $def .= ' and admin_id = '.(empty($aid) ? 0 : $aid);
            }

            $total = $this->model->where($def)->where($where)->count();

            $list = $this->model->field('tit,create_time,status,userid,taskid,remark,admin_id')
                    ->where($def)->where($where)
                    ->order($sort,$order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();

            //拆分用户表
            if(empty($userid)){
                $userids = array_unique(array_column($list,'userid'));
                $userinfo = Db::name('domain_user')->whereIn('id',$userids)->column('uid','id');
            }
            //拆分昵称表
            if(empty($aid)){
                $aids = array_unique(array_column($list,'admin_id'));
                $adinfo = Db::name('admin')->whereIn('id',$aids)->column('nickname','id');
            }

            foreach($list as &$v){

            	$v['status'] = $fun->getStatus($v['status'],['<span style="color: gray;">已提交</span>','<span style="color: green;">hold成功</span>','<span style="color: red;">hold失败</span>']);
                if(mb_strlen($v['remark']) > 10){
                    $v['remark'] = $fun->returntitdian($v['msg'],10).' <a href="javascript:showRemark(\''.$v['remark'].'\');">查看</a>';
                }
                if(empty($userid)){
                    $v['group'] = isset($userinfo[$v['userid']]) ? $userinfo[$v['userid']] : '-';
                }else{
                    $v['group'] = $uid;
                }

                if(empty($aid)){
                    $v['special_condition'] = isset($adinfo[$v['admin_id']]) ? $adinfo[$v['admin_id']] : '-';
                }else{
                    $v['special_condition'] = $nickname;
                }


            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


}
