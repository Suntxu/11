<?php

namespace app\admin\controller\domain\access;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\common\sendMail;
use app\admin\library\Redis;
/**
 * 域名转回记录
 *
 * @icon fa fa-user
 */
class Shows extends Backend
{

    protected $model = null;
    
    /**
     * @var \app\admin\model\HZ
     */
    protected $fun = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_access_show');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model
                        ->field('id,domain,status,audittime,remark')->where($where)
                        ->order($sort,$order)->limit($offset, $limit)
                        ->select();
            $fun = Fun::ini();
            foreach($list as $k => &$v){
                $v['status'] = $fun->getStatus($v['status'],['待审核','转入中','转入成功','转入失败','已取消']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('id'));
        return $this->view->fetch();
    }

    /*
     *  详情转入域名 修改状态
     */
    public function  UpdateS(){
        $this->request->filter(['strip_tags']);
        $params = $this->request->post();
        $ids = $params['id'];
        $status = intval($params['status']);
        if($ids){
            $sj = time();
            $info = $this->model->alias('s')->join('domain_access a','s.aid=a.id','left')->where('s.status = 0 and a.audit = 0')->whereIn('s.id',$ids)->field('a.bath,a.userid,a.id')->find();
            if(!$info)
                exit('转入域名不存在或已审核');
            if($status == 1){
                // 更改状态 并发送到队列
                $redis = new Redis(['select' => 2]);
                $redis -> lpush('domain_access',$info['id']);
                // 修改状态  
                $this->model->whereIn('id',$ids)->update(['status'=>1]);
            }else{
                // 修改状态
                $this->model->whereIn('id',$ids)->update(['status'=>3,'audittime' => $sj,'remark' => $params['remark']]);
            }
            // 查询批次是否修改完
            $flag = $this->model->field('id')->where('status = 0  and aid = '.$info['id'])->find();
            if(empty($flag)){
                // 查看是否都审核失败
                $error = $this->model->where('aid',$info['id'])->column('status');
                $fz = array_unique($error);
                if(count($fz) == 1 && $fz[0] == 3){
                    //修改批次状态
                    Db::name('domain_access')->where('id',$info['id'])->update(['remark'=>'审核失败','audit'=>2]);
                    // 发送失败邮件
                    $sendMail = new sendMail();
                    $uid = Db::name('domain_user')->where('id = '.$info['userid'])->value('uid');
                    $remark = '<a href="'.WEBURL.'user#/user/access/opshow/id/'.$info['id'].'">请去转入详情页面查看</a>';
                    $sendMail -> ingeinto($info['userid'],$uid,$remark,$info['bath']);
                }else{
                    //修改批次状态
                    Db::name('domain_access')->where('id',$info['id'])->update(['remark'=>'任务执行完成','audit'=>1]);
                }
            }
            echo '操作成功';
            die;
        }else{
            echo '请先选择要审核的域名！';
            die;
        }
    }
    /**
     * 下载txt全部域名
     */ 
    public function download(){
        
        $bid = intval($this->request->get('pid'));
        $domain = $this->model->where(['aid'=>$bid])->column('domain');
        return Fun::ini()->txtFile($domain);

    }


}