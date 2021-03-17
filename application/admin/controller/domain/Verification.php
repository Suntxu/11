<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\common\sendMail;

/**
 * 举报管理
 *
 * @icon fa fa-user
 */
class Verification extends Backend
{
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = Db::name('domain_verification')->where($where)->count();
            $list = Db::name('domain_verification')
                ->field('id,tit,gtype,otype,status,remark,create_time,zcs')
                ->where($where)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            $cates = $this->getCates();
            foreach ($list as $k => &$v) {
                $v['zcs'] = $cates[$v['zcs']];
                $v['gtype'] = $fun->getStatus($v['gtype'],['自查','用户举报']);
                $v['otype'] = $fun->getStatus($v['otype'],['警告整改','申请hold']);
                $v['op'] = $v['status'];
                $v['status'] = $fun->getStatus($v['status'],['<span style="color:red;">处罚中</span>','<span style="color:blue">已解除</span>']);
                if( mb_strlen($v['remark']) > 20){
                    $v['vremakr'] = $v['remark'];
                    $v['remark'] = $fun->returntitdian($v['remark'],20).'&nbsp;&nbsp;<span style="cursor:pointer;color:#0066FF;text-decoration:underline;" id="remark'.$v['id'].'" >查看</a>';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    public function add(){

        if($this->request->isPost()){
            $data = $this->request->post('row/a');

            if(empty($data['tit']) || empty($data['remark'])){
                $this->error('缺少重要参数');
            }
            
            $domain = Fun::ini()->moreRow($data['tit']);
            if(count($domain) > 100){
                $this->error('每次最多可添加100个域名');
            }
            $domains = Db::name('domain_pro_n')->field('zcs,tit,zt,userid')->whereIn('tit',$domain)->select();
            if(empty($domains)){
                $this->error('域名不存在域名库中');
            }

            if($data['otype'] == 1){
                $zts = array_unique(array_column($domains,'zt'));
                if(in_array(6,$zts)){
                    $this->error('域名包含正在回收中的域名,不能做申请hold');
                }

            }

            $pars = [];
            $time = time();
            foreach($domains as $k => $v){ 
                $pars[$k]['tit'] = $v['tit'];
                $pars[$k]['gtype'] = $data['gtype'];
                $pars[$k]['otype'] = $data['otype'];
                $pars[$k]['zcs'] = $v['zcs'];
                $pars[$k]['remark'] = $data['remark'];
                $pars[$k]['create_time'] = $time;
            }
            Db::startTrans();
            Db::name('domain_verification')->insertAll($pars);

            if($data['otype'] == 1){ //同步冻结域名

                $zts = array_unique(array_column($domains,'zt'));
                if(in_array(6,$zts)){
                    $this->error('域名包含正在回收中的域名,不能做申请hold');
                }
                $tits = array_column($domains,'tit');
                //增加操作记录
                Db::name('domain_operate_record')->insert(['create_time'=>time(),'tit'=>implode(',',$tits),'operator_id'=>$this->auth->id,'type'=>1,'value'=>'冻结'.'__域名自查操作；原因:'.$data['remark']]);
                
                Db::name('domain_pro_n')->whereIn('tit',$tits)->setField('status',4);
                
                Db::name('domain_pro_trade')->whereIn('tit',$tits)->setField('lock',4);

                $this->sendFrezeeDomainsNotice($domains,1,$data['remark']);
            }

            Db::commit();

            $this->success('添加成功');
        }
        $ids = $this->request->get('id');
        $domains = Db::name('domain_pro_n')->whereIn('id',$ids)->column('tit');
        $this->view->assign(['domains' => $domains]);
        return $this->view->fetch();
    }
    /**
     * 修改
     */
    public function modi(){
        if($this->request->isAjax()){
            $ids = $this->request->get('id');
            if(empty($ids)){
                $this->error('缺少重要参数');
            }
            $flag = Db::name('domain_verification')->field('tit,otype')->where(['status' => 0,'id' => $ids ])->find();
            $flag || $this->error('该记录已经解除处罚!');

            $flag['userid'] = Db::name('domain_pro_n')->where('tit',$flag['tit'])->value('userid');

            Db::startTrans();

            if($flag['otype'] == 1){ //同步解除域名冻结
                Db::name('domain_pro_n')->where('tit',$flag['tit'])->setField('status',0);
                
                Db::name('domain_pro_trade')->whereIn('tit',$flag['tit'])->setField('lock',0);
                //增加操作记录
                Db::name('domain_operate_record')->insert(['create_time'=>time(),'tit'=>$flag['tit'],'operator_id'=>$this->auth->id,'type'=>1,'value'=>'恢复正常'.'__域名自查操作']);
                $this->sendFrezeeDomainsNotice([$flag],2);
            }

            Db::name('domain_verification')->where('id',$ids)->setField('status',1);
            Db::commit();
            $this->success('操作成功！');
        }
    }

    /**
     * 冻结域名发送通知
     */
    private function sendFrezeeDomainsNotice($dinfo,$status,$cause=''){
        //查询用户名并发邮件通知
        $uids = Db::name('domain_user')->field('id,uid')->whereIn('id',array_unique(array_column($dinfo,'userid')))->select();
        //拼接数组
        $uinfos = [];
        foreach($uids as $k => $v){
            $uinfos[$k] = $v;
            foreach($dinfo as $vv){
                if($vv['userid'] == $v['id']){
                    $uinfos[$k]['tit'][] = $vv['tit'];
                }
            }   
        }

        $send = new sendMail();
        foreach($uinfos as $v){
            $send->domainFrezee($v['id'],$v['uid'],$status,$v['tit'],$cause);
        }

    }

}
