<?php

namespace app\admin\controller\total\issue;

use app\common\controller\Backend;
use think\Db;

/**
 * 域名过户问题排查
 */
class Transfer extends Backend
{
    private $connect = null;

    public function _initialize()
    {
        global $remodi_db;
        $this->connect = Db::connect($remodi_db);
        parent::_initialize();
    }

    //查看列表
    public function index(){

        if($this->request->isAjax()){

            list($where, $sort, $order, $offset, $limit,$uid) = $this->buildparams();

            $def = 'type = 2 ';
            if($uid){
                $uid = trim($uid);
                $userid = Db::name('domain_user')->where('uid',$uid)->value('id');
                $def .= ' and userid = '.($userid ? $userid : 0);
            }
            $total = $this->connect->name('domain_link_show_info')->where($where)->where($def)->count();

            $list = $this->connect->name('domain_link_show_info')
                ->field('id,create_time,userid')
                ->where($where)->where($def)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();

            if(empty($userid)){
                $userids = array_unique(array_column($list,'userid'));
                $uinfo = Db::name('domain_user')->whereIn('id',$userids)->column('uid','id');
            }

            foreach($list as &$v){
                if(empty($userid)){
                    $v['group'] = isset($uinfo[$v['userid']]) ? $uinfo[$v['userid']] : '--';
                }else{
                    $v['group'] = $uid;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    //获取实名信息
    public function show(){
        //获取持有人信息
        $param = $this->request->get();
        $uid = empty($param['uid']) ? '' : strip_tags($param['uid']);
        if(empty($uid)){
            $this->error('缺少重要参数');
        }
        $this->view->assign('uid',$uid);
        return $this->view->fetch();

    }

    //添加域名
    public function add()
    {
        if ($this->request->isPost()) {
            $uid = $this->request->post('uid');
            if(empty($uid)){
                $this->error('请输入用户名');
            }
            $userid = Db::name('domain_user')->where('uid',$uid)->value('id');
            if(empty($userid)){
                $this->error('用户不存在');
            }
            $flag = $this->connect->name('domain_link_show_info')->where(['type' => 2,'userid' => $userid])->count();
            if($flag){
                $this->error('用户：'.$uid.'已存在!');
            }

            $this->connect->name('domain_link_show_info')->insert([
                'tit' => '',
                'create_time' => time(),
                'userid' => $userid,
                'type' => 2
            ]);

            $this->success('添加成功');

        }
        return $this->view->fetch();
    }

    public function del($ids=null){
        if($this->request->isAjax()){
            if(empty($ids)){
                $this->error('缺少重要参数');
            }

            $this->connect->name('domain_link_show_info')->where('type',2)->whereIn('id',$ids)->delete();

            $this->success('删除成功');

        }
    }

}
