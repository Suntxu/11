<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 域名手动续费
 */
class Renewal extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post("row/a");
            if(!$row['ttxt']){
                $this->error('请输入要续费的域名');
            }
            $a = Fun::ini()->moreRow($row['ttxt']);
            if(count($a) > 500){
            	$this->error('一次最多可填写20个域名');
            }
            if(empty($row['uid'])){
                $this->error('请输入用户名');
            }
            if(empty($row['money'])){
                $this->error('请输入域名单价');
            }
            $money = floatval($row['money']);
            if(empty($money)){
                $this->error('单价金额格式不正确');
            }
            $uinfo = Db::name('domain_user')->field('id,money1')->where('uid',$row['uid'])->find();
            if(empty($uinfo)){
                $this->error('用户不存在,请确认!');
            }
            // 查找域名是否存在
            $info = Db::name('domain_pro_n')->field('tit,dqsj')->whereIn('tit',$a)->select();
            $tits = array_column($info, 'tit');
            if(empty($info)){
            	$this->error('您输入的域名不存在域名库中');
            }

            //计算金额
            $titTotal = count($tits);
            $money = sprintf('%.2f',($titTotal * $money));
            if($money > $uinfo['money1']){
                $this->error('账户余额不足');
            }
            Fun::ini()->lockMoney($uinfo['id']) || $this->error('系统繁忙,请稍后操作');
            $tableName = 'update '.PREFIX.'domain_pro_n ';
			$tableName1 = 'update '.PREFIX.'domain_pro_trade ';
			
			$sql = ' set dqsj = case tit ';

			foreach($info as $k => $v){
				$sql .= ' when "'.$v['tit'].'" then "'.date('Y-m-d H:i:s',strtotime('+1 year'.$v['dqsj'])).'"';
			}

			$sql .= ' end where tit in("'.implode('","', $tits ).'")';
			Db::startTrans();
			Db::query($tableName.$sql);
			Db::query($tableName1.$sql);
			Db::name('domain_user')->where('id',$uinfo['id'])->setDec('money1',$money);
            $rid = Db::name('domain_operate_record')->insertGetId([
                'tit' => implode(',', $tits),
                'operator_id' => $this->auth->id,
                'create_time' => time(),
                'type' => 8,
                'value' => '增加1年',
            ]);
            $msg = '手动续费'.$titTotal.'个域名,续费1年,共花费'.$money.'元。';
            Db::name('flow_record')->insert([
                'sj' => date('Y-m-d H:i:s'),
                'infoid' => $rid,
                'product' => 0,
                'subtype' => 20,
                'balance' => ($uinfo['money1'] - $money),
                'money' => -$money,
                'userid' => $uinfo['id'],
                'info' => implode(',',$tits),
                'uip' => ' ',
            ]);
            
            Db::commit();
            Fun::ini()->unlockMoney($uinfo['id']);
            $ar = array_diff($a,$tits);
            if($ar){
            	$msg .= ',以下域名不在域名库中,已过滤:'.implode(',',$ar);
            }

            $this->success($msg,'reload');  
        }
        return $this->view->fetch();
    }

}
