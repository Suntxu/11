<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 *  域名需求
 */
class Domaindemand extends Backend
{
    public function index(){
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$bargain) = $this->buildparams();
            
            $def = '';

            if($bargain){
                $def = 'd.budget '.($bargain == 1 ? '=' : '>').' '.'0';
            }

            $total = Db::name('domain_demand')
                    ->alias('d')
                    ->join('domain_user u', 'd.user_id=u.id','left')
                    ->join('admin a','d.admin_id=a.id','LEFT')
                    ->where($where)->where($def)
                    ->count();
            
            $list = Db::name('domain_demand')
                    ->alias('d')
                    ->join('domain_user u', 'd.user_id=u.id','left')
                    ->join('admin a','d.admin_id=a.id','LEFT')
                    ->field('a.nickname,d.handle_time,d.refuse_txt,d.details,d.id,u.uid,d.title,d.budget,d.contact_qq,d.contact_tel,d.addtime,d.status')
                    ->limit($offset, $limit)
                    ->where($where)->where($def)
                    ->order($sort)
                    ->select();

            $fun = Fun::ini();
            foreach ($list as $k => $v){
                $list[$k]['uid'] = isset($v['uid']) ? $v['uid'] : '游客';
                $list[$k]['d.budget'] = $v['budget'] != 0 ? $v['budget'] : '议价';
                $list[$k]['d.contact_qq'] = $v['contact_qq'];
                $list[$k]['d.contact_tel'] = $v['contact_tel'];
                $list[$k]['d.addtime'] = $v['addtime'];
                $list[$k]['d.handle_time'] = $v['handle_time'];
                $list[$k]['d.status'] = $v['status'];
                $list[$k]['a.nickname'] = $v['nickname'];
                if ($v['status'] == 0) {
                    $list[$k]['manmage'] = "&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(1,{$v['id']})'>同意</a>&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(2,{$v['id']})'>拒绝</a>";
                }

                if(mb_strlen($v['details']) > 15){
                    $list[$k]['details'] = $fun->returntitdian($v['details'],15).'<span class="show_value" style="cursor:pointer;color:#3c8dbc;" onclick="showRemark(\''.$v['details'].'\')" >查看</span>';
                }
                if(mb_strlen($v['refuse_txt']) > 15){
                    $list[$k]['refuse_txt'] = $fun->returntitdian($v['refuse_txt'],15).'<span class="show_value" style="cursor:pointer;color:#3c8dbc;" onclick="showRemark(\''.$v['refuse_txt'].'\')" >查看</span>';
                }
                $list[$k]['group'] = '';

            }
            return $result = json(["total" => $total, "rows" => $list]);
        }
        return $this->view->fetch();
    }   
    
    
    /*
    同意
    */
    public function agree(){
        if ($this->request->isAjax())
        {
            $ids = $this->request->param('id');
            if(empty($ids)){
                $this->error('缺少重要参数');
            }
            $info = Db::name('domain_demand')->where('status',0)->whereIn('id',$ids)->column('id');
            if(empty($info)){
                $this->error('域名需求申请状态值已更改,请刷新后操作');
            }
            $res = Db::name('domain_demand')->whereIn('id',$info)->update(['status'=>1,'admin_id'=>$this->auth->id,'handle_time'=>time()]);
            if ($res) {
                return json(['code'=>1,'msg'=>'审核成功']);
            }else{
                return json(['code'=>0,'msg'=>'审核失败']);
            }
        }
        
    }
    
    //拒绝
    public function refuse(){
        if ($this->request->isAjax())
        {
            $id = $this->request->post('id');
            $txt = $this->request->post('txt');
            if (empty($txt) || empty($id)) {
                $this->error('参数错误');
            }
            $info = Db::name('domain_demand')->where('status',0)->whereIn('id',$id)->column('id');
            if(empty($info)){
                $this->error('未处理域名属性申请不存在');
            }
            
            $updateInfo = [
                        'status' => 2,
                        'handle_time' => time(),
                        'admin_id' => $this->auth->id,
                        'refuse_txt' => $txt,
                    ];
            $res = Db::name('domain_demand')->whereIn('id',$info)->update($updateInfo);
            if ($res) {
                return json(['code'=>1,'msg'=>'审核成功']);
            }else{
                return json(['code'=>0,'msg'=>'审核失败']);
            }
        }
    }
    
    
}
