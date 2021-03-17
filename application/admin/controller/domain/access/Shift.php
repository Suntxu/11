<?php

namespace app\admin\controller\domain\access;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\sendMail;
use app\admin\library\Redis;
use think\Config;
/**
 * 域名转入记录
 *
 * @icon fa fa-user
 */
class Shift extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_access');
    }
    /**
     * 查看
     */
    public function index($ids = '')
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('b')->join('domain_user u','u.id=b.userid')->where($where)->count();
            $list = $this->model->alias('b')->join('domain_user u','u.id=b.userid')
                    ->field('b.bath,b.audit,b.email,b.subdate,b.finishdate,b.id,b.dcount,u.uid,b.reg_id,b.api_id')
                    ->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();

            $apis = $this->getApis(-1);

            foreach($list as $k => $v){
                $list[$k]['manmage'] = "<a  href='/admin/domain/access/shows?id={$v['id']}' class='btn-dialog' >查看</a>";
                if($v['audit']==1){
                    $list[$k]['audit'] = '<a style="color:green">执行成功(<span title="共计'.$v['dcount'].'条" style="color:green;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==0){
                    $list[$k]['audit'] = '<a style="color:red">待审核(<span title="共计'.$v['dcount'].'条" style="color:green;">'.$v['dcount'].'</span>)';
                    $list[$k]['manmage'] .= "&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(1,{$v['id']})'>成功</a>&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(2,{$v['id']})'>失败</a>";
                }elseif($v['audit'] == 3){
                    $list[$k]['audit'] = '<a style="color:gray">任务执行中(<span title="共计'.$v['dcount'].'条" style="color:gray;">'.$v['dcount'].'</span>)';
                }else if($v['audit'] == 2){
                    $list[$k]['audit'] = '<a style="color:red">审核失败(<span title="共计'.$v['dcount'].'条" style="color:red;">'.$v['dcount'].'</span>)';
                }else{
                    $list[$k]['audit'] = '<a style="color:gray">用户取消(<span title="共计'.$v['dcount'].'条" style="color:red;">'.$v['dcount'].'</span>)';
                }
                if(isset($apis[$v['api_id']])){
                    $list[$k]['b.reg_id'] = $apis[$v['api_id']]['regname'];
                    $list[$k]['b.api_id'] = $apis[$v['api_id']]['tempid'];
                }else{
                    $list[$k]['b.reg_id'] = '--';
                    $list[$k]['a.api_id'] = '--';
                }
                // 执行情况
//                $sql = 'SELECT count(if(status=0,1,null)) as wsh,count(if(status=1,1,null)) as zrz,count(if(status=2,1,null)) as suc,count(if(status=3,1,null)) as err from '.PREFIX.'domain_access_show where aid = '.$v['id'];
//                $res = Db::query($sql);
//                $list[$k]['status_remark'] = '<span style="color:red">待审核:'.$res[0]['wsh'].'条</span>-<span style="color:gray">转入中:'.$res[0]['zrz'].'条</span>-<span style="color:green">成功:'.$res[0]['suc'].'条</span>-<span style="color:red">失败:'.$res[0]['err'].'条</span>';

                $list[$k]['u.uid'] = $v['uid'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$ids);
        $this->assignconfig('fail_select',Config::get('domain_shift_fail_select'));
        return $this->view->fetch();
    }
    /*
    列表页转回域名 修改状态
     */
    public function  UpdateS(){
        
        $params = $this->request->post();
        $id =  is_array($params['id']) ? $params['id'] : [$params['id']];
        $status = intval($params['status']);
        if($id){
			$sj = time();
			$info = $this->model->whereIn('id',$id)->where('audit','0')->field('bath,userid,sxf')->select();
			if(count($info) != count($id)){
                exit('该批次含有已审核的记录,请确认!');
            }
            Db::startTrans();
            if($status == 1){
                //修改状态
                $this->model->whereIn('id',$id)->update(['remark'=>$params['remark'],'audit'=>3,'admin_id' => $this->auth->id]);
                Db::name('domain_access_show')->whereIn('aid',$id)->update(['status'=>1]);

                // 更改状态 并发送到队列
                $redis = new Redis(['select' => 2]);
                array_map(function($n) use($redis) { $redis->lrem('domain_access',0,$n);$redis->lpush('domain_access',$n);  },$id);
            }else{
                $this->model->whereIn('id',$id)->update(['remark'=>$params['remark'],'audit'=>2,'finishdate' => $sj,'admin_id' => $this->auth->id]);
                Db::name('domain_access_show')->whereIn('aid',$id)->update(['status'=>3,'audittime' => $sj,'remark' => $params['remark']]);
                // 发送邮件通知
                $sendMail = new sendMail();
                foreach($info as $v){
                    $uid = Db::name('domain_user')->where('id = '.$v['userid'])->value('uid');
                    $sendMail -> ingeinto($v['userid'],$uid,$params['remark'],$v['bath']);
                }
            }
            Db::commit();
            echo '操作成功';
            die;
        }else{
            echo '请先选择要审核的域名！';
            die;
        }


    }



}