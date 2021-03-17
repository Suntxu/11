<?php

namespace app\admin\controller\vipmanage\service;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 关联记录
 * @icon fa fa-user
 */
class Record extends Backend
{
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('exclusive_record');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit,$special) = $this->buildparams();
            $total = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')->join('domain_user u1','l.kefu=u1.id','left')->join('domain_user u2','l.change=u2.id','left')
                          ->where($where)
                          ->count();
            $list = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')->join('domain_user u1','l.kefu=u1.id','left')->join('domain_user u2','l.change=u2.id','left')
                         ->field('u.uid,u1.uid as u1id,u2.uid as u2id,l.time,l.type,l.status,l.id,l.audit_time')
                         ->where($where)->order($sort,$order)->limit($offset, $limit)
                         ->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['u.uid'] = $v['uid'];
                $v['u1.uid'] = empty($v['u1id']) ? '官网' : $v['u1id'];

                $v['u2.uid'] = empty($v['u2id']) && $v['type'] == 3 ? '官网' : $v['u2id'];
                $v['l.type'] = $fun->getStatus($v['type'],['--','怀米大使默认绑定','会员中心第一次绑定','更换绑定']);
                $v['l.status'] = $fun->getStatus($v['status'],['<span style="color:red">审核中</span>','<span style="color:green">审核成功</span>','<span style="color:red">审核失败</span>']);
                $v['l.time'] = $v['time'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    public function edit($ids=null){
        if($this->request->isAjax()){
            $param = $this->request->post('row/a');
            if(!intval($param['id']) || !intval($param['status'])){
                $this->error('参数不正确');
            }
            $data = $this->model->field('userid,kefu,change')->where(['id' => $param['id'],'status' => 0])->find();
            if(empty($data)){
                $this->error('记录不存在,请确认!');
            }

            if($data['kefu']){
                //怀米大使判断
                $eid = Db::name('domain_promotion')->where('userid',$data['kefu'])->value('id');
                if(empty($eid)){
                    $this->error('怀米大使不存在,请确认!');
                }
            }
            
            $time = time();
            //更新状态
            Db::startTrans();
            try{
                if($param['status'] == 1){
                    $old = Db::name('exclusive_user')->where('userid',$data['userid'])->value('userid');
                    if($old){
                        Db::name('exclusive_user')->where('userid',$data['userid'])->update([
                            'kfuserid' => $data['kefu'],
                            'time' => $time,
                            'type' => 3,
                        ]);
                    }else{
                        Db::name('exclusive_user')->insert([
                            'userid' => $data['userid'],
                            'kfuserid' => $data['kefu'],
                            'time' => $time,
                            'type' => 3,
                        ]);
                    }

                    if($data['kefu']){
                        $res = Db::name('domain_promotion_relation')->where('userid',$data['userid'])->value('userid');
                        $endtime = strtotime('+10 year');
                        if($res){
                            Db::name('domain_promotion_relation')->where('userid',$data['userid'])->update([
                                'relation_id' => $data['kefu'],
                                'status' => 1,
                                'type' => 1,
                                'rtime' => $time,
                            ]);
                        }else{
                            Db::name('domain_promotion_relation')->insert([
                                'relation_id' => $data['kefu'],
                                'userid' => $data['userid'],
                                'status' => 1,
                                'type' => 1,
                                'rtime' => $time,
                            ]);
                        }

                        //查找关联记录是否存在 如果有记录 把之前的记录的到期时间改成现在的
                        $seLogId = Db::name('domain_promotion_relation_log')->where(['userid' => $data['userid']])->where('etime > '.$time)->order('etime desc')->value('id');
                        if($seLogId){
                            Db::name('domain_promotion_relation_log')->where('id',$seLogId)->update(['etime' => $time]);
                        }

                        Db::name('domain_promotion_relation_log')->insert([
                            'relation_id' => $data['kefu'],
                            'userid' => $data['userid'],
                            'stime' => $time,
                            'etime' => $endtime,
                        ]);

                    }else{ //更换官网客服 解除绑定
                        Db::name('domain_promotion_relation')->where('userid',$data['userid'])->delete();
                        Db::name('domain_promotion_relation_log')->where('userid',$data['userid'])->delete();
                        $param['remark'] = $param['remark'].',更换官网客服,自动解除怀米大使关联!';
                    }
                        
                }
                $this->model->where('id',$param['id'])->update(['status' => $param['status'],'remark' => $param['remark'],'audit_time' => $time ]);
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('操作成功');
        }
        $data = $this->model->alias('l')->join('domain_user u','l.userid = u.id','left')->join('domain_user u1','l.kefu=u1.id','left')->join('domain_user u2','l.change=u2.id','left')
            ->field('u.uid,u1.uid as u1id,u2.uid as u2id,l.time,l.msg,l.status,l.id,l.remark,l.type')
            ->where('l.id',$ids)
            ->find();

        $data['u1id'] = empty($data['u1id']) ? '官网' : $data['u1id'];
        $data['u2id'] = empty($data['u2id']) && $data['type'] == 3 ? '官网' : $data['u2id'];

        $this->assign('data',$data);
        return $this->fetch();
    }

}
