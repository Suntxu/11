<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\common\sendMail;

/**
 * 提现承诺信息记录
 *
 * @icon fa fa-user
 */
class Txpromise extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('tixian_pledge');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('p')->join('domain_user u','p.userid=u.id')->join('domain_tixian t','t.id=p.tid')
                          ->where($where)
                          ->count();

            $list = $this->model->alias('p')->join('domain_user u','p.userid=u.id')->join('domain_tixian t','t.id=p.tid')
                    ->field('p.id,p.status,p.type,p.datum,p.create_time,p.remark,t.txzh,t.txname,u.uid')
                    ->where($where)->order($sort, $order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();

            foreach($list as $k => $v){

              $list[$k]['p.create_time'] =  $v['create_time'];

              if(mb_strlen($v['remark']) > 15){
                    $list[$k]['remark'] = $fun->returntitdian($v['remark'],15).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['remark'].'\')" >查看更多</span>'; 
              }

              $list[$k]['p.type'] = $fun->getStatus($v['type'],['<span style="color:red;">身份证正面照片</span>','<span style="color:green;">身份证反面照片</span>','<span style="color:orange;">手持身份证正面照片</span>','<span style="color:pink;">承诺书照片</span>','<span style="color:blue;">企业营业执照照片</span>']);

              $list[$k]['p.status'] = $fun->getStatus($v['status'],['<span style="color:gray;">未审核</span>','<span style="color:green;">审核成功</span>','<span style="color:red;">审核失败</span>']);

              $list[$k]['datum'] = WEBURL.'uploads/finance/'.$v['datum'];
              $list[$k]['t.txname'] = $v['txname'];
              $list[$k]['t.txzh'] = $v['txzh']; 

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 审核
     */
    public function audit(){

      if($this->request->isAjax()){
        
        $params = $this->request->param();
        if(empty($params['id']) || empty($params['status'])){
          $this->error('缺少重要参数');
        }

        if(!in_array($params['status'],[1,2])){
          $this->error('参数取值不正确,请联系管理员！');
        }

        $remark = empty($params['remark']) ? '已通过' : $params['remark'];

        $info = $this->model->alias('p')->join('domain_user u','p.userid=u.id')->join('domain_tixian t','t.id=p.tid')
          ->field('p.id,p.userid,p.type,t.txzh,u.uid')
          ->where(['p.id' => intval($params['id']),'p.status' => 0])
          ->find();

        if(empty($info)){
          $this->error('该记录已审核,请刷新页面！');
        }

        $this->model->where('id',$info['id'])->update(['status' => $params['status'],'remark' => $remark]);

        if($params['status'] == 2){
          // 发送通知
          $e = new sendMail();
          $re = Fun::ini()->getStatus($info['type'],['身份证正面照片','身份证反面照片','手持身份证正面照片','承诺书照片','企业营业执照照片']);
          //发送邮件
          $e->withdrawPromise($info['userid'],$info['uid'],$info['txzh'],2,$re);
        }

        $this->success('操作成功');
      }
    }


}
