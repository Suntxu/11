<?php
	
namespace app\admin\controller\domain\reserve;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis; 
use app\admin\common\sendMail;
use app\admin\library\AliyunApi;
use fast\Http;
/**
 * 域名预定 单个处理方法
 */
class Reserveop extends Backend
{
	protected $redis = null;  
	 /**
     * 单个处理
     * @param  Array $row 表单数据    
     * @param  Array $info 域名/保证金等信息
     * @return void       
     */
   	public function __construct($redis = null){
	    if(empty($redis)){
            $this->redis = new Redis(['select' => 2]);
	    }else{
            $this->redis = $redis;
	    }
   	}
    public function singleReserve($row,$info,$admin_id){
        $sj = time();
        //获取域名信息
        $reserveInfo = Db::name('domain_order_reserve_info')->where(['tit' => $info['tit'],'status' => 0,'type' => 0])->field('len,del_time as zcsj,hz,tit,reg_time,del_time')->find();
        if(empty($reserveInfo)){
            $this->error('域名有误');
        }
        if($row['status'] == 1){ //预定成功
           
            $reserveInfo = $this->getStoreDomainInfo($row['api_id'],$reserveInfo,$info['userid'],$sj);
            
            Fun::ini()->lockFreezing($info['userid']) || $this->error('系统繁忙,请稍后操作!');
            Db::startTrans();
            //扣除保证金 
            $m1 = Db::execute('update '.PREFIX.'domain_user set baomoney1 = baomoney1 -'.$info['moneynum'].',money1 = money1 -'.$info['moneynum'].' where id = '.$info['userid']);
            //入库
            $i1 = Db::name('domain_pro_n')->insert($reserveInfo);
            
            
            $umoney = Db::name('domain_user')->where('id',$info['userid'])->value('money1');
            //插入流水表
            $i2 = Db::name('flow_record')->insert([
                    'sj'    => $info['sj'],
                    'infoid'=> $info['rid'],
                    'product'=> 0,
                    'subtype'=> 13,
                    'uip'   => $info['uip'],
                    'balance' => $umoney,
                    'money' => -$info['moneynum'],
                    'userid'=> $info['userid'],
                ]);

            $rupdate = ['pstatus' => 3,'status' => 7, 'endtime' => $sj,'fmoney' => $info['moneynum'],'admin_id' => $admin_id ];
            // //如果是怀米大使  就返佣 暂时先注释掉
            if(DOMAIN_RESERVE_REBATE > 0){

                $relation_id = Db::name('domain_promotion_relation')->where('userid',$info['userid'])->value('relation_id');
                if($relation_id){

                    $yj = ($info['moneynum'] * (DOMAIN_RESERVE_REBATE / 100) );

                    Db::name('spreader_flow')->insert([
                        'buyeruserid' => $info['userid'],
                        'type' => 1,
                        'infoid' => $info['rid'],
                        'paymoney' => $info['moneynum'],
                        'yj' => $yj,
                        'time' => $sj,
                        'yjtype' => 3,
                        'userid' => $relation_id,
                    ]);
                    $rupdate['yj'] = $yj;
                    $rupdate['tuserid'] = $relation_id;
                }
            }
            

            $m2 = Db::name('domain_baomoneyrecord')->where('id',$info['id'])->update(['status' => 1,'otime' => $sj ,'sremark' => '域名:'.$info['tit'].' 预定成功']);

            $m3 = Db::name('domain_order_reserve')->where('id',$info['rid'])->update($rupdate);
            
            $m4 = Db::name('domain_order_reserve_info')->where(['tit' => $reserveInfo['tit'],'status' => 0,'type' => 0])->setField('status',1);

            if($m1 == $m2 && $m1 == $m3 && $m1 == $i1 && $m1 == $i2 && $m1 == $m4){
                Db::commit();
                Fun::ini()->unlockFreezing($info['userid']);
                if($info['userid'] == 5947){
                    //入库到erp
                    $this->saveErpStore($info['tit'],$reserveInfo['hz']);
                }

                //域名检测类型
                $dredis = new Redis();
                $dredis->lpush('domain_pro_n',$info['tit']);

            }else{
                Db::rollback();
                Fun::ini()->unlockFreezing($info['userid']);
                return $this->error('数据更新失败');
            }
        }elseif($row['status'] == 3){ //失败
            Fun::ini()->lockBaoMoney($info['userid']) || $this->error('系统繁忙,请稍后操作!');
            Db::startTrans();
            //退回保证金
            $m1 = Db::name('domain_user')->where('id',$info['userid'])->setDec('baomoney1',$info['moneynum']);
            //还原保证金
            $m2 = Db::name('domain_baomoneyrecord')->where('id',$info['id'])->update(['status' => 2,'otime' => $sj,'sremark' => '域名:'.$info['tit'].' 预定失败']);
            //更改状态
            $m3 = Db::name('domain_order_reserve')->where('id',$info['rid'])->update(['status' => 3, 'endtime' => $sj,'admin_id' => $admin_id]);
            $m4 = Db::name('domain_order_reserve_info')->where(['tit' => $reserveInfo['tit'],'status' => 0,'type' => 0])->setField('status',3);
            if($m1 == $m2 && $m1 == $m3 && $m1 == $m4){
                Db::commit();
                Fun::ini()->unlockBaoMoney($info['userid']);
            }else{
                Db::rollback();
                Fun::ini()->unlockBaoMoney($info['userid']);
                return $this->error('数据更新失败');
            }
        }
        $sendMail = new sendMail();
        $sendMail->domainReserve($info,$row['status']); //预定失败/成功
        return $this->success('操作完成');
    }
    //多个处理
    public function betchReserve($row,$info,$auction,$admin_id){

        //根据域名获取订单信息用于修改保证金状态和保证金状态
        $orderInfo = Db::name('domain_order_reserve')->where(['tit' => $info['tit'],'type' => 0])->whereIn('status',[0,9])->where('time > '.strtotime('-3 month') )->field('id,userid')->order('time desc')->select();
        $sendMail = new sendMail();
        if($row['status'] == 1){ //多人预定成功 
            if( empty($auction['money'])){ //empty($auction['min_rate']) ||
                $this->result('',300,'检测到域名有多人预定,请输入竞拍参数!');
            }
            // $auction = [];
            $auction['tit'] = $info['tit'];
            $auction['cur_money'] = $info['moneynum'];
            $auction['hz'] = $info['hz'];
            $auction['dtype'] = $info['dtype'];
            $sj = time();
            //结束时间超过当前晚上九点 就按第二天的早成七点算
            $auction['start_time'] = $sj;
            if(date('H') < 19){
                $endtime = strtotime(date('Y-m-d 19:00:00'));
            }else{
                $endtime = strtotime('+1 day '.date('Y-m-d 19:00:00'));
            }
            $olen = count($orderInfo);
            $auction['lx_userid'] = $orderInfo[$olen - 1]['userid'];
            $auctionEndtime = $this->redis->get('auction_endtime');
            if(empty($auctionEndtime) || date('H', $auctionEndtime) == 21){
                $auction['end_time'] = $endtime;
                $this->redis->set('auction_endtime',$endtime);
            }else{
                $endtimeTen = $auctionEndtime + 600;
                $auction['end_time'] = $endtimeTen;
                $this->redis->set('auction_endtime',$endtimeTen);
            }
            if($this->redis->ttl('auction_endtime') <= 0){
                // 时间戳到期时间 晚上7点 
                $tomore = $endtime - $sj;
                $this->redis->expire('auction_endtime',$tomore);
            }
            //多条记录
            Db::startTrans();
            //插入竞拍参数
            $i1 = Db::name('domain_auction_info')->insertGetId($auction);
            //添加记录表
            $record = [];
            foreach($orderInfo as  $v){
                $record[] = ['money' => 0, 'auction_id' => $i1, 'time' => $sj, 'type' => 4, 'userid' => $v['userid'], 'res_money' => $info['moneynum']];
            }
            $i2 = Db::name('domain_auction_record')->insertAll($record);
            //更改订单状态为竞拍 --
            $m1 = Db::name('domain_order_reserve')->whereIn('id',array_column($orderInfo,'id'))->update(['status' => 2,'auction_id' => $i1,'admin_id' => $admin_id]);
            $m2 = Db::name('domain_order_reserve_info')->where(['tit' => $info['tit'],'status' => 0,'type' => 0])->setField('status',2);
            if($i1 && $m1 && $m2 && $i2){
                Db::commit();
            }else{
                Db::rollback();
                return $this->error('数据更新失败');
            }
            $this->redis->hMset('reserve_domain_'.$info['tit'],$row);
            $this->redis->lpush('auction_domain_info',$i1);
            $info['start_time'] = $auction['start_time'];
            $info['end_time'] = $auction['end_time'];
            $sendMail->domainReserve($info,2); //竞拍
        }else{ 
            //竞拍失败 存入redis
            $this->savaRedis(3,[$info['tit']],$admin_id);
            $sendMail->domainReserve($info,3);//预定失败
        }
        return $this->success('操作完成');
    }

    /**
     * 获取域名入库参数
     */
    public function getStoreDomainInfo($apiid,$reserveInfo,$userid,$sj){

        $apiInfo = Db::name('domain_api')->where('id',$apiid)->field('api,regid,region,accessKey,secret')->find();
        if($apiInfo['regid'] == 66){ //阿里云

            $aliSend = new AliyunApi($apiInfo['region'],$apiInfo['accessKey'],$apiInfo['secret']);
            $res = $aliSend->querySingleDomainInfo($reserveInfo['tit']);
            if(!empty($res['RegistrationDate']) && !empty($res['ExpirationDate'])){
                $reserveInfo['zcsj'] = $res['RegistrationDate'];
                $reserveInfo['dqsj'] = $res['ExpirationDate'];
            }
        
        }elseif($apiInfo['regid'] == 77){//纳点网
            $cmd = '/domain/productInfo';
            $requestDate = ['keyword' => $reserveInfo['tit']];
            $res = Fun::ini()->requestNadianApi($apiInfo,$sj,$cmd,$requestDate,'POST');
            if($res['code'] == 0000000 && $res['data']['state'] == '正常'){
                $reserveInfo['zcsj'] = $res['data']['starTime'];
                $reserveInfo['dqsj'] = $res['data']['endTime'];
            }
        }
        
        if(empty($reserveInfo['zcsj']) || empty($reserveInfo['dqsj'])){
            if($reserveInfo['reg_time'] > 0){
                $year = date('Y') + 1;
                $reserveInfo['zcsj'] = date($year.'-m-d H:i:s',$reserveInfo['reg_time']);
            }else{
                //采用删除时间加1天
                $reserveInfo['zcsj'] = date('Y-m-d H:i:s',strtotime('+1 day',$reserveInfo['del_time']));
            }
            $reserveInfo['dqsj'] = date('Y-m-d H:i:s',strtotime(' +1 year '.$reserveInfo['zcsj'] ));
        }

        unset($reserveInfo['reg_time']);
        unset($reserveInfo['del_time']);

        $reserveInfo['zcs'] = $apiInfo['regid'];
        $reserveInfo['api_id'] = $apiid;
        $reserveInfo['inserttime'] = $sj;
        $reserveInfo['zt'] = 9;
        $reserveInfo['userid'] = $userid;
        $reserveInfo['special'] = 2;
        $reserveInfo['hz'] = '.'.$reserveInfo['hz'];
       

        return $reserveInfo;

    }

    /**
     * 入库erp
     */
    public function saveErpStore($tit,$hz){
        // 成本价
        $Cost = \think\Config::get('reserve_cost');
        $url = 'http://erp-210.huaimi.com/api/product/add';
        $params = [
            'title' => $tit,
            'unitCost' => $Cost[$hz],
            'admin' => 16,
            'category_id' => 105,
            'd_type' => 112,
        ];
       
        return Http::post($url,$params);
    }
	/**
     * 存入redis域名信息
     */ 
    public function savaRedis($row,$domains,$admin_id){
        if(is_array($row)){ //预定成功
            foreach ($domains as $k => $v) { //个人
                $this->redis->lRem('reserve_success_domain',0,$v);
                $this->redis->LPush('reserve_success_domain',$v);
                $this->redis->hMset('reserve_domain_'.$v,$row);
            }
            $status = 6;
            $infozt = 1;
        }else{ // 失败处理
            foreach ($domains as $k => $v) { //竞拍
                $this->redis->lRem('reserve_fial_domain',0,$v);
                $this->redis->LPush('reserve_fial_domain',$v);
            }
            //更新订单状态
            $status = 5;
            $infozt = 3;
        }
        Db::name('domain_order_reserve')->whereIn('status',[0,9])->whereIn('tit',$domains)->where('type',0)->update(['status' => $status,'admin_id' => $admin_id]);
        Db::name('domain_order_reserve_info')->whereIn('tit',$domains)->where(['status' => 0,'type' => 0])->setField('status',$infozt);
    }


}