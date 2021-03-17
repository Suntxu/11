<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 用户管理--精简版
 *
 * @icon fa fa-user
 */
class Usersop extends Backend
{
    protected $model = null;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_user');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit,$uid) = $this->buildparams();

            $def = '1 = 1';

            $fun = Fun::ini();
            
            if($uid){
                $uid = trim($uid);
                $def .= ' and uid like "%'.str_replace('@','~',$uid).'%" or uid like "%'.$uid.'%" ';
            }
            $total = $this->model
                ->where($where)->where($def)
                ->count();
            
            $list = $this->model->field('id,uid,uqq,money1,uip,mot,zt,baomoney1,(money1 - baomoney1) as balance')
                ->where($where)->where($def)
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();

            foreach($list as &$v){

                $v['zt'] = $fun->getStatus($v['zt'],['--','正常使用','邮箱未激活','禁用','安全码错误过多','<span style="color: orange;">注销审核中</span>','<span style="color:red;">已注销</span>']);

                if(strpos($v['uid'],'~')){
                    $uids = explode('_',$v['uid']);
                    $v['group'] = isset($uids[1]) ? str_replace('~','@',$uids[1]) : '--';
                }else{
                    $v['group'] = $v['uid'];
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 跳转到后台
     */
    public function Jump($ids=''){
        if($ids){
            $flag = $this->request->get('flag','');
            $sj = time();
            // 新用户中心
            if($flag == 'new'){
                $token = 'fdsafHHf~`HMjJ^FF';
                $rnd = md5('time='.$sj.'&uid='.$ids.$token);
            }else{
                $aa = '1e4343a123434fds1fdaf4fwafd**7#!~';
                $rnd = Fun::ini()->rands();
                $token = md5($aa.$rnd);
                $this->model->where(['id'=>$ids])->update(['webtoken'=>$token,'totime'=>$sj,'weburl'=>'/user/']);
            }
            return json(['code'=>0,'token'=>$rnd,'uid'=>$ids,'time'=>$sj,'admin_id' => $this->auth->id,'weburl'=>WEBURL]);
        }else{
            return json(['code'=>1,'msg'=>'参数错误']);
        }
    }

}
