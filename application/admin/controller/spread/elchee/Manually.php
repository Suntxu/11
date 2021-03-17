<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 手动添加怀米大使
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class Manually extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_pro_trade');
    }
    
    /**
     * 
     * 查看
     */
    public function index()
    {
        if ($this->request->isPost()) {
            
            $uids = $this->request->post("uids");
            
            if(!$uids){
                $this->error('请输入要修改的用户名');
            } 
            $uid = Fun::ini()->moreRow($uids);

            //用户名判断
            $ninfo = Db::name('domain_user')->field('id,uid')->whereIn('uid',$uid)->select();

            $uids = array_column($ninfo,'uid');

            if(count($uid) != count($ninfo)){

                $ntit = array_diff($uid,$uids);

                $this->error('用户:'.implode(',',$ntit).'不是怀米网用户,请确认！');

            }

            $userids = array_column($ninfo,'id');

            $uarr = array_combine($userids, $uids);

            //查询是否已经是怀米大使
            $pinfo = Db::name('domain_promotion')->whereIn('userid',$userids)->column('userid');
            if($pinfo){
                $nuid = '';
                foreach($pinfo as $v){
                    $nuid .= $uarr[$v].',';
                }
                $this->error('用户:'.rtrim($nuid,',').'已经成为怀米大使了！');
            }

            $time = time();
            $arr = [];
            foreach($userids as $v){
                $arr[] = ['userid' => $v,'ctime' => $time,'uid' => '','status' => 1,'remark' => '手动添加'];
            }

            Db::name('domain_promotion')->insertAll($arr);

            $this->success('添加成功!');
        }
        return $this->view->fetch();
    }

}
