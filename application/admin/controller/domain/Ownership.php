<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\library\Redis;
use app\admin\common\Fun;
use app\admin\library\Pinyin;
/**
 * 35互联过户记录
 *
 * @icon fa fa-user
 */
class Ownership extends Backend
{
    private $redis = null;
    protected $noNeedRight = ['index','reinfo','show','download'];
    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->redis = new Redis();
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            $def = ' r.tasktype = 1 and d.api_id = 30 ';

            $tasklist = $this->redis->lrange('hander_update_contact',0,-1);
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $taskarr = empty($tasklist) ? 0 : implode(',',$tasklist);
            $sfl = '';
            if($group === ''){
                $sfl = '';
            }else if($group == 0){
                $sfl = ' and r.id in ( '.$taskarr.' )';
            }elseif($group == 1){
                $sfl = ' and r.id not in ( '.$taskarr.' )';
            }

            $total = Db::table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail_1'.' d','r.id=d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                    ->where($def.$sfl)->where($where)
                    ->count('DISTINCT r.id');

            $list = Db::table(PREFIX.'Task_record')->alias('r')->join(PREFIX.'Task_Detail_1'.' d','r.id=d.taskid','left')->join('domain_user u','r.userid=u.id','left')
                        ->field('u.uid,r.createtime,r.remark,r.id,r.status,r.uip,count(if(d.TaskStatusCode = 0,1,null)) as total,count(*) as bbs')
                        ->where($def.$sfl)->where($where)
                        ->order($sort,$order)->limit($offset, $limit)
                        ->group('r.id')
                        ->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                if($sfl){
                    $sfl = str_replace('r.id','taskid',$sfl);
                }
                $conm = 'SELECT count(if(TaskStatusCode=0,1,null)) as n, count(*) as zn FROM '.PREFIX.'Task_Detail_1'.' WHERE api_id = 30 '.$sfl;
            }else{
                $conm = 'SELECT count(if(d.TaskStatusCode=0,1,null)) as n, count(*) as zn FROM '.PREFIX.'Task_Detail_1'.' d LEFT JOIN '.PREFIX.'Task_record as r ON d.taskid = r.id  LEFT JOIN '.PREFIX.'domain_user as u ON r.userid=u.id '.$sql.' and d.api_id = 30 and d.TaskStatusCode = 0'.$sfl;
            }
            $qi = Db::query($conm);
            $fun = Fun::ini();

            //获取已处理当未结束任务的值
            $ltasks = $this->redis->lrange('ownership_task_id',0,-1);
            foreach($list as $k => &$v){
                $v['r.createtime'] = $v['createtime'];
                $v['r.id'] = $v['id'];
                if(in_array($v['id'],$ltasks)){
                    $v['r.id'] .= '&nbsp;<span style="color:red;">处理中</span>';
                }
                $v['temp'] = '查看';
                //判断子任务已操作 主任务ID还有别的API在执行 
                if($v['total'] == 0){
                    $v['group'] = '执行完成';
                    $v['status'] = 1;
                }else{
                    $v['group'] = $fun->getStatus($v['status'],['执行中','执行完成']);
                }
                $v['total'] = '<font color="red">'.$v['total'].'</font>/<font color="green">'.$v['bbs'].'</font>';

                $v['num'] = '<font color="red">'.$qi[0]['n'].'</font>/<font color="green">'.$qi[0]['zn'].'</font>';
                
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        
        return $this->view->fetch();
    }

    /**
     * 认证信息
     */
    public function reinfo(){
        $ids = $this->request->get('id');
        $info = Db::table(PREFIX.'domain_infoTemplate')->alias('t')->join('user_renzheng r','r.id=t.renzheng_id')
            ->field('r.*,t.Telephone,t.Email,t.RegistrantType,t.newstime')
            ->where('t.id',$ids)
            ->find();
        if(empty($info)){
            $this->error('无数据');
        }
        $info['yxing'] =  Pinyin::pinyin($info['xing']);
        $info['yming'] =  Pinyin::pinyin($info['ming']);
        $info['yZhProvince'] =  Pinyin::pinyin($info['ZhProvince']);
        $info['yZhCity'] =  Pinyin::pinyin($info['ZhCity']);
        $info['yaddress'] =  Pinyin::pinyin($info['address']);
        $this->view->assign('data',$info);
        return $this->view->fetch();

    }

    /**
     * 查询域名详情
     */
    public function show(){

        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::table(PREFIX.'Task_Detail_1')->where($where)->where('api_id',30)->count();

            $list = Db::table(PREFIX.'Task_Detail_1')
                        ->field('tit,TaskStatusCode,ErrorMsg,CreateTime,id')
                        ->where($where)->where('api_id',30)
                        ->order($sort,$order)->limit($offset, $limit)
                        ->select();

            $fun = Fun::ini();
            foreach($list as $k => &$v){
                $v['TaskStatusCode'] = $fun->getStatus($v['TaskStatusCode'],['执行中',2 => '执行成功','执行失败']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('ids',$this->request->get('id'));
        return $this->view->fetch();
    }
    /**
     * 处理中
     */
    public function modiStatus(){
        if($this->request->isAjax()){
            $id = $this->request->get('id');
            if($id){

                $task = Db::table(PREFIX.'Task_record')->where(['id' => $id,'status' => 0])->count();
                if(empty($task)){
                    $this->error('记录状态不正确');
                }
                
                $year = $this->getTaskYear($id);
                
                $tit = Db::table(PREFIX.'Task_Detail_1'.$year)->where(['taskid' => $id,'api_id' => 30,'TaskStatusCode' => 0])->column('tit');

                $this->insertOpRecord(implode(',',$tit),'正在处理_'.$id);

                $this->redis->lrem('ownership_task_id',0,$id);
                //存入redis
                $this->redis->lpush('ownership_task_id',$id);
                $this->success('处理成功');
            }

        }
    }
    /**
     * 批量 域名修改
     */
    public function edit($ids=null){

        if($this->request->isAjax()){
            
            if(!intval($ids)){
                $this->error('缺少重要参数');
            }
            $remark = $this->request->post('remark','');
            $year = $this->getTaskYear($ids);
            //获取域名
            $tname = PREFIX.'Task_Detail_1'.$year;
            $domains = Db::table($tname)->where(['api_id' => 30,'taskid' => $ids,'TaskStatusCode' => 0])->column('tit');

            if(empty($domains)){
                $this->error('子任务状态不正确或域名不存在！');
            }

            if(empty($remark)){ //成功
                $tstatus = 1;
                $dstatus = 2;
                $emsg = '处理成功';
            }else{
                $tstatus = 2;
                $dstatus = 3;
                $emsg = '处理失败:'.$remark;
            }
            $sj = time();
            Db::startTrans();
            try{

                $this->insertOpRecord(implode(',',$domains),$emsg.'_'.$ids,$sj);
                //更新主库状态
                Db::name('domain_pro_n')->whereIn('tit',$domains)->setField('infoZR',$tstatus);
                //更新子表状态
                Db::table($tname)->where(['taskid' => $ids,'TaskStatusCode' => 0,'api_id' => 30])->update(['TaskStatusCode' => $dstatus,'ErrorMsg' => $remark,'CreateTime' => $sj]);
                Db::commit();
            }catch(Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }

            //删除队列里面的api ID
            $this->redis->lrem('upinfo_task_all_api_'.$ids,0,30);
            $this->redis->lrem('hander_update_contact',0,$ids);
            $this->redis->lrem('ownership_task_id',0,$ids);
            $this->success('主任务ID为 '.$ids.' 的过户记录已操作成功!');

        }
    }

    /**
     *  单个修改
     */
    public function single(){

        if($this->request->isPost()){
            $params = $this->request->post('row/a');
           
            if(empty($params['status']) || empty($params['domain'])){
                $this->error('缺少重要参数');
            }
            if($params['status'] == 1){
                $dstatus = 2;
                $emsg = '处理成功';
            }else{
                if(empty($params['remark'])){
                    $this->error('请填写失败原因');
                }
                $dstatus = 3;
                $emsg = '处理失败:'.$params['remark'];
            }
            $year = $this->getTaskYear($params['taskid']);

            
            $tname = PREFIX.'Task_Detail_1'.$year;

            $domain = Fun::ini()->moreRow($params['domain']);
            $dwhere = ['taskid' => $params['taskid'],'api_id' => 30,'TaskStatusCode' => 0];

            $domains = Db::table($tname)->where($dwhere)->whereIn('tit',$domain)->column('tit');
            if(empty($domains)){
                $this->error('域名已执行完成');
            }
            $sj = time();
            Db::startTrans();
            try{
                $this->insertOpRecord(implode(',',$domains),$emsg.'_'.$params['taskid'],$sj);
                //更新主库状态
                Db::name('domain_pro_n')->whereIn('tit',$domains)->setField('infoZR',$params['status']);
                //更新子表状态
                Db::table($tname)->where($dwhere)->whereIn('tit',$domains)->update([
                    'TaskStatusCode' => $dstatus,
                    'ErrorMsg' => $params['remark'],
                    'CreateTime' => $sj,
                ]);
                Db::commit();
            }catch(Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }

            //判断是否删除key
            $total = Db::table($tname)->where($dwhere)->count();

            if(empty($total)){
                //删除队列里面的api ID
                $this->redis->lrem('upinfo_task_all_api_'.$params['taskid'],0,30);
                $this->redis->lrem('hander_update_contact',0,$params['taskid']);
                $this->redis->lrem('ownership_task_id',0,$params['taskid']);
            }
            $this->success('过户记录已操作成功!');
        }
        $ids = $this->request->get('ids');
        $taskid = $this->request->get('taskid');
        $year = $this->getTaskYear($taskid);
        $data = Db::table(PREFIX.'Task_Detail_1'.$year)->where(['taskid' => $taskid])->whereIn('id',$ids)->column('tit');
        $this->view->assign(['domains' => $data,'taskid' => $taskid]);
        return $this->view->fetch();
    }
    /**
     * 到处未过户域名
     */
    public function download(){
        $id = $this->request->get('tid');
        if(intval($id)){
            $year = $this->getTaskYear($id);
            $domain = Db::table(PREFIX.'Task_Detail_1'.$year)->where(['taskid' => $id,'api_id' => 30,'TaskStatusCode' => 0])->column('tit');
            Fun::ini()->txtFile($domain);
            die;
        }

    }

    /**
     * 插入操作记录
     */
    private function insertOpRecord($tit,$value,$sj = null){
        $sj = empty($sj) ? time() : $sj;
        Db::name('domain_operate_record')->insert([
            'tit' => $tit,
            'operator_id' => $this->auth->id,
            'create_time' => $sj,
            'type' => 7,
            'value' => $value,
        ]);
    }

}
