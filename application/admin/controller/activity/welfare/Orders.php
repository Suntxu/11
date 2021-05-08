<?php

namespace app\admin\controller\activity\welfare;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 活动注册订单
 * @icon fa fa-user
 */
class Orders extends Backend
{
    /**
     * @var
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_welfare_order');
    }
    
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) { 

            list($where, $sort, $order, $offset, $limit,$flag) = $this->buildparams();
            
            $time = time();

            $def = '1 = 1 ';

            if($flag == 1){ // 未完成
                $def = ' o.end_time > '.$time.' and  o.domain_total > (o.success_num + o.reging_num)';
            }else if($flag == 2){ //已完成
                $def = 'o.domain_total = (o.success_num + o.reging_num)';
            }else if($flag == 3){ //已结束
                $def = ' o.end_time < '.$time.' and o.domain_total > (o.success_num + o.reging_num)';
            }
            
            $total = $this->model->alias('o')->join('domain_welfare_meal m','o.hd_id=m.id')->join('domain_user u','o.userid=u.id')
                    ->where($where)->where($def)
                    ->count();
            
            $list = $this->model->alias('o')->join('domain_welfare_meal m','o.hd_id=m.id')->join('domain_user u','o.userid=u.id')
                    ->field('o.id,o.create_time,o.start_time,o.end_time,o.hz,o.domain_total,o.discount_cost,o.success_num,o.reging_num,o.api_id,o.uip,o.title as otitle,o.cost,o.status,o.remark,m.title,u.uid')
                    ->where($where)->where($def)->order($sort,$order)
                    ->limit($offset, $limit)
                    ->select();
            
            //注册量
            $num = $this->model->alias('o')->join('domain_welfare_meal m','o.hd_id=m.id')->join('domain_user u','o.userid=u.id')
                    ->field('sum(o.discount_cost* o.domain_total) as zdis,sum(o.cost* o.domain_total) as zcost,sum(o.success_num) as snum,sum(o.reging_num) as rnum')->where($where)->where($def)
                    ->find();

            $fun = Fun::ini();
            $apiInfo = $this->getApis();

            foreach($list as &$v){

                if(mb_strlen($v['title']) > 15){
                    $v['m.title'] = $fun->returntitdian($v['title'],15).' <span onclick="showRemark(\''.$v['title'].'\')" style="color:#52a8f1;cursor: pointer;">查看</span>';;                    
                }else{
                    $v['m.title'] = $v['title'];
                }
                $v['o.id'] = $v['id'];
                $v['o.start_time'] = $v['start_time'];
                $v['o.end_time'] = $v['end_time'];

                if(mb_strlen($v['otitle']) > 15){
                    $v['o.title'] = $fun->returntitdian($v['otitle'],15).' <span onclick="showRemark(\''.$v['otitle'].'\')" style="color:#52a8f1;cursor: pointer;">查看</span>';;                    
                }else{
                    $v['o.title'] = $v['otitle'];
                }

                $v['o.create_time'] = $v['create_time'];


                $v['o.api_id'] = isset($apiInfo[$v['api_id']]) ? $apiInfo[$v['api_id']] : '--';

                $v['o.domain_total'] = $v['domain_total'];
                $v['o.discount_cost'] = $v['discount_cost'];
                $v['o.cost'] = $v['cost'];
                $v['snum'] = $num['snum'];
                $v['rnum'] = $num['rnum'];
               
                $v['zdis'] = sprintf('%.2f',$num['zdis']);
                $v['zcost'] = sprintf('%.2f',$num['zcost']);

                $v['remark'] = ' <span onclick="showRemark(\''.$v['remark'].'\')" style="color:#52a8f1;cursor: pointer;">查看</span>';;                    

                if($v['reging_num'] == 0 && $v['end_time'] > $time){
                    //可注册数量
                    $kto = $v['domain_total'] - $v['success_num'];
                    if($kto){
                        if($v['status'] == 0){
                            $v['op'] = 1; //暂停 退款
                        }elseif($v['status'] == 1){
                            $v['op'] = 2; //恢复正常 退款
                        }else{
                            $v['op'] = 0;
                        }
                    }else{
                        $v['op'] = 0;
                    }
                }else{
                    $v['op'] = 3;//不展示
                }

                $v['o.status'] = $fun->getStatus($v['status'],['<span style="color:green;">正常</span>','<span style="color:red;">已暂停</span>','<span style="color:orange;">已退款</span>']);

                $v['group'] = ''; //特殊状态
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        $param = $this->request->get();

        $this->view->assign([
            'mid' => isset($param['mid']) ? $param['mid'] : '', //套餐列表
            'id' => isset($param['id']) ? $param['id'] : '', //资金明细
        ]);
        return $this->view->fetch();
    }


    /**
     * 开启 暂停
     */
    public function modiStatus(){

        if($this->request->isAjax()){

            $param = $this->request->param();
            
            if(empty($param['id']) || empty($param['status'])){
                $this->error('缺少重要参数');
            }
            
            if(!in_array($param['status'], [1,2])){
                $this->error('状态参数不在取值范围内');
            }

            $status = $param['status'] == 1 ? 0 : 1;

            $remark = empty($param['remark']) ?  '启用订单' : $param['remark'] ; 

            $this->checkOrders($param['id'],$param['status']);

            $this->model->where('id',$param['id'])->update(['status' => $status,'remark' => $remark]);  

            $this->success('操作成功');

        }

    }

    /**
     * 退款
     */
    public function refund(){

        if($this->request->isAjax()){

            $param = $this->request->param();
            
            if(empty($param['id']) || empty($param['remark'])){
                $this->error('缺少重要参数');
            }

            $info = $this->checkOrders($param['id'],3);

            //计算退款金额
            $money = sprintf('%.2f',($info['domain_total'] - $info['success_num']) * $info['discount_cost']);
            
            //锁住用资金锁
            Fun::ini()->lockMoney($info['userid']) || $this->error('系统繁忙,请稍后操作');

            //获取用户余额
            $umoney = Db::name('domain_user')->where('id',$info['userid'])->value('money1');

            Db::startTrans();
            try{

                //修改状态
                $this->model->where('id',$param['id'])->update(['status' => 2,'remark' => $param['remark']]);
                //退款
                Db::name('domain_user')->where('id',$info['userid'])->setInc('money1',$money);
                //资金明细
                Db::name('flow_record')->insert([
                    'sj' => date('Y-m-d H:i:s'),
                    'infoid' => $param['id'],
                    'product' => 8,
                    'subtype' => 21,
                    'uip' => $info['uip'],
                    'money' => $money,
                    'userid' => $info['userid'],
                    'remark' => $param['remark'],
                    'balance' => ($umoney+$money),
                ]);
               

            }catch(Exception $e){

                Db::rollback();

                Fun::ini()->unlockMoney($info['userid']);

                $this->error($e->getMessage());
            }
            Db::commit();
            Fun::ini()->unlockMoney($info['userid']);

            $this->success('操作成功');

        }

    }

    /**
     * 修改结束时间
     */
    public function modiEndtime(){
        
        if($this->request->isAjax()){
            $param = $this->request->param();
            if(empty($param['remark']) || empty($param['id'])){
                $this->error('缺少重要参数');
            }
            
            if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$param['remark'])){
                $this->error('结束时间格式不正确,请确认!');
            }

            $endtime = strtotime($param['remark']);

            if(!$endtime){
                $this->error('结束时间格式不正确,请确认!');
            }

            $info = $this->model->field('reging_num,domain_total,success_num')->where('id',$param['id'])->find();

            if(empty($info)){
                $this->error('订单不存在,请确认!');
            }
            if(($info['domain_total'] - $info['reging_num'] - $info['success_num']) == 0 ){
                $this->error('订单已无可注册的域名');
            }

            $this->model->where('id',intval($param['id']))->setField('end_time',$endtime);

            $this->success('修改成功');

        }

    }

    /**
     * 查询是否有注册中的域名
     */
    private function checkOrders($id,$status){

        $def = 'id = '.$id;
        $field = 'reging_num,end_time';
        if($status == 1){ //恢复正常
            $def .= ' and status = 1';
        }elseif($status == 2){ //暂停
            $def .= ' and status = 0';
        }else{ //退款
            $def .= ' and status in (0,1)';
            $field .= ',userid,discount_cost,success_num,domain_total,uip';
        }

        $info = $this->model->field($field)->where($def)->find();
        if(!$info){
            $this->error('记录状态已发生改变,请刷新后重试！');
        }

        if($info['reging_num']){
            $this->error('该订单有正在注册中的域名,不能修改状态!');
        }

        if($info['end_time'] < time()){
            $this->error('订单已到结束,不可进行操作!');
        }


        return $info;
    }



}
