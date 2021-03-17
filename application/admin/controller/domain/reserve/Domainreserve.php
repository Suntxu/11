<?php

namespace app\admin\controller\domain\reserve;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
use app\admin\common\sendMail;
use app\admin\controller\domain\reserve\Reserveop;

/**
 * 域名预定
 *
 * @icon fa fa-user
 */
class Domainreserve extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['checkUpod'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_order_reserve');
    }
    /**
     * 查看
     */
    public function index(){
        if ($this->request->isAjax()){
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            if($group){
                $def = ' i.type = 0 and r.type = 0  and i.hz = "'.ltrim($group,'.').'"';
            }else{
                $def = 'i.type = 0 and r.type = 0 ';
            }

            $total = $this->model->alias('r')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')
                    ->where($where)->where($def)->group('r.tit,r.endtime')
                    ->count();
            $list = $this->model->alias('r')->join('domain_order_reserve_info i','r.tit=i.tit')->join('domain_auction_info a','a.id=r.auction_id','left')
                    ->field('r.tit,r.time,r.status,r.endtime,r.pstatus,r.money,r.id,count(*) as renshu,i.del_time,a.end_time,i.hz,i.dtype')
                    ->where($where)->where($def)->order($sort,$order)
                    ->limit($offset,$limit)
                    ->group('r.tit,r.endtime')
                    ->select();
            $fun = Fun::ini();
            $sj = time();
            foreach($list as $k=>&$v){
                $v['r.tit'] = $v['tit'];
                $v['r.money'] = $v['money'];
                $v['r.time'] = $v['time'];
                $v['r.endtime'] = $v['endtime'];
                $v['group'] = $v['hz'];
                $v['r.api_id'] = '';
                $v['i.dtype'] = $v['dtype'];
                if($v['status'] == 7){

                    $v['r.pstatus'] = $fun->getStatus($v['pstatus'],['<span style="color: red">未支付</span>','<span style="color: orangered">未交割</span>','<span style="color: orange">交割失败</span>','<span style="color: green">已交割</span>','<span style="color: gray">违约</span>']);

                }elseif($v['status'] == 0 ){

                    $v['r.pstatus'] = '<span style="color: orange">未开始</span>';
                    $v['special_condition'] = '<span style="color: gray">未处理</span>';

                }elseif ($v['status'] == 9){

                    $v['r.pstatus'] = '<span style="color: orange">未开始</span>';

                }else{

                    $v['r.pstatus'] = '<span style="color: darkorange">未得标</span>';

                }
                $v['i.del_time'] = $v['del_time'];
                $stats = $this->model->where('tit',$v['tit'])->column('status');
                if(!empty($v['end_time']) && $v['end_time'] < $sj){
                    $v['status'] = '<span style="color:green">竞价已结束</span>';
                }else{
                    if(in_array(0,$stats)){
                        $v['status'] = '进行中';
                    }elseif(in_array(9,$stats)){
                        $v['status'] = '<span style="color:gray">已提交</span>';
                    }elseif( in_array(7,$stats) || in_array(8,$stats) ){
                        if($v['renshu'] > 1){
                            $v['status'] = '<span style="color:green">竞价已结束</span>';
                        }else{
                            $v['status'] = '<span style="color:green">预定成功</span>';
                        }
                    }elseif(in_array(4,$stats)){
                        $v['status'] = '<span style="color:blue">竞价成功但未付款</span>';
                    }elseif(in_array(2,$stats)){
                        $v['status'] = '<span style="color:pink">竞拍进行中</span>';
                    }elseif(in_array(5,$stats)){
                        $v['status'] = '<span style="color:orange">批量失败进行中</span>';
                    }elseif(in_array(6,$stats)){
                        $v['status'] = '<span style="color:yellowgreen">批量成功进行中</span>';
                    }elseif(in_array(3,$stats)){
                        $v['status'] = '<span style="color:red">预定失败</span>';
                    }elseif(in_array(1,$stats)){
                        $v['status'] = '<span style="color:green">已预定</span>';
                    }
                }
            }
            
            return json(['total'=>$total,'rows'=>$list]);
        }
        return $this->view->fetch();
    }
    
    /**
     * 审核
     */
    public function edit($ids=null){

        !empty($ids) || $this->error('参数有误');

        if($this->request->isPost()){
            $row = $this->request->post('row/a');
            if(empty($row['status'])){
                $this->error('请选择预定状态');
            }
            if($row['status'] == 1){
                if(empty($row['zcs']) || empty($row['api_id'])){
                    $this->error('请选择注册商及接口');
                }
            }
            //判断是否为单个域名
            $info = $this->model->alias('r')->join('domain_baomoneyrecord b','r.id=b.infoid')->join('domain_order_reserve_info i','i.tit=r.tit')
                ->where(['r.id' => $ids,'b.type' => 5,'b.status' => 0,'r.type' => 0])->whereIn('r.status',[0,9])->where('r.time >'.strtotime('-3 month') )
                ->field('b.id,b.uip,b.userid,b.moneynum,r.tit,b.sj,r.id as rid,i.del_time,r.type,i.dtype,i.hz')
                ->find();

            if(empty($info)){
                $this->error('该记录已经审核过!');
            }
            
            $coun = $this->model->where(['tit' => $info['tit']])->column('id'); //查找是否是多人预定
            // 如果状态为9 就直接修改 不用往下执行
            if($row['status'] == 9){

                $this->model->whereIn('id',$coun)->update(['status' => 9,'admin_id' => $this->auth->id]);
                $this->setLoadDomainStatus([ $info['tit'] => $info['del_time'] ]);
                $this->success('状态修改成功');
            }

            if($row['status'] == 1 && time() < $info['del_time']){
                $this->error($info['tit'].'删除时间大于现在的时间,您还不能进行操作!');
            }
            
            $op = new Reserveop();
            if(count($coun) == 1){  //单个处理
                $op->singleReserve($row,$info,$this->auth->id);
            }else{
                $auction = $this->request->post('au/a');
                $op->betchReserve($row,$info,$auction,$this->auth->id);
            }
        }
        $data = $this->model->alias('r')->join('domain_auction_info a','a.id=r.auction_id','left')->join('domain_user u','u.id=r.userid')
                ->field('r.tit,r.time,r.money,r.status,r.endtime,u.uid,r.id,a.end_time')
                ->where('r.id',$ids)
                ->find();
        $status = $data['status'];
        if($status == 0 || $status == 9){
            $status = [0,9];
        }
	
        if($status == 6){
            $redis = new Redis(['select' => 2]);
            $api = $redis->hgetall('reserve_domain_'.$data['tit']);
            $data['apiName'] = Db::name('domain_api')->where('id',$api['api_id'])->value('tit');
        }
        $data['yuding'] = $this->model->where(['tit' => $data['tit']])->whereIn('status',$status)->count(); //查找是否是多人预定
        if(!empty($data['end_time']) && $data['end_time'] < time()){
            $data['status'] = '<span style="color:green">竞价已结束</span>';
        }else{
            $data['status'] = Fun::ini()->getStatus($data['status'],['进行中','--','<span style="color:pink">竞拍中</span>','<span style="color:red">预定失败</span>','--','<span style="color:orange">批量失败进行中</span>','<span style="color:yellowgreen">批量成功进行中</span>','<span style="color:green">预定成功</span>','<span style="color:green">竞价已结束</span>','已提交','<span style="color:red">外部领先</span>']);
        }
        // 获取注册商列表
        $list = Db::name('category')->field('id,name')->where(['type'=>'api','status'=>'normal'])->whereIn('id',[66,77])->select();
        $this->view->assign(['data' => $data,'zcs' => $list]);
        return $this->view->fetch();
    }

    /**
     * 检测是否是多人预定
     * @return [type] [description]
     */
    public function checkUpod(){
        $domains = $this->request->post('domains');
        if($domains){
            $olddomains = Fun::ini()->moreRow($domains);
            $threeMonth = strtotime('-3 month');
            //查看是否含有多个预定
            $domainGroup = $this->model->whereIn('tit',$olddomains)->where('type = 0 and time > '.$threeMonth)->whereIn('status',[0,9])
                    ->field('count(*) as num')
                    ->group('tit')
                    ->select();
            if(count($domainGroup) != array_sum(array_column($domainGroup,'num'))){
                return ['code' => 1,'msg' => '域名被多人预定'];
            }else{
                return ['code' => 0,'msg' => 'ok'];
            }
        }
    }
    /**
     * 按域名处理
     */
    public function multire(){

        if($this->request->isPost()){
            //验证域名 --采取过滤
            $row = $this->request->post('row/a');
            if(empty($row['status']) || empty($row['domain']) ){
                $this->error('请选择必要参数！');
            }
            $olddomains = Fun::ini()->moreRow($row['domain']);
            if(count($olddomains) > 200){
                $this->error('域名每次最多提交200个');
            }
            unset($row['domain']);
            if($row['status'] == 1 && empty($row['zcs'])){
                $this->error('请选择注册商');
            }
            $threeMonth = strtotime('-3 month');
            //查看是否含有单个预定
            $domainGroup = $this->model->alias('r')->join('domain_order_reserve_info i','i.tit=r.tit')
                    ->whereIn('r.tit',$olddomains)->where('r.type = 0 and r.time > '.$threeMonth)->whereIn('r.status',[0,9])
                    ->field('r.tit,count(*) as num,i.del_time')
                    ->group('r.tit')
                    ->select();
            $length = count($domainGroup);
            if($length == 0){
                $this->error('此批域名已经被处理过!');
            }
            $domains = array_column($domainGroup,'tit');
            // 如果状态为9 就直接修改 不用往下执行
            if($row['status'] == 9){

                $this->model->whereIn('tit',$domains)->update(['status' => 9,'admin_id' => $this->auth->id]);

                $iarr = array_combine(array_column($domainGroup, 'tit'),array_column($domainGroup, 'del_time'));

                $this->setLoadDomainStatus($iarr);

                $this->success('状态修改成功');
            
            }
            $sj = time();
            // if($row['status'] == 1){

            // }
            // 过滤删除日期大于现在的订单
            if($row['status'] == 1){
                $filterArr = [];
                $domainGroup = array_filter($domainGroup,function($v) use (&$filterArr,$sj){
                    if($v['del_time'] > $sj){
                        $filterArr[] = $v['tit'];
                        return false;
                    }
                    return true;
                });

                if($filterArr){
                    $this->error('域名'.implode(',',$filterArr).'的删除时间大于现在的时间,您无法进行操作！');
                }
            }
          
            $batch = []; //多个处理
            $beerr = []; //单个域名处理失败
            $saveRedis = []; //存入redis
            $auction = $this->request->post('au/a');
            
            foreach ($domainGroup as $k => $v) {
                if($length > 1){ //输入多个域名处理
                    if($v['num'] > 1){
                        if($row['status'] == 1){ //处理多个订单 失败 存入redis 成功直接处理
                            $batch[] = $v['tit'];
                        }else{
                            $saveRedis[] = $v['tit'];
                        }
                    }else{
                        if($row['status'] == 1){ //处理单个订单 成功存入redis 失败直接处理
                            $saveRedis[] = $v['tit'];
                        }else{
                            $beerr[] = $v['tit'];
                        }
                    }

                }else{ //单个直接处理
                    $info = $this->model->alias('r')->join('domain_baomoneyrecord b','r.id=b.infoid')->join('domain_order_reserve_info i','i.tit=r.tit')
                        ->where(['r.tit' => $v['tit'],'b.type' => 5,'b.status' => 0])
                        ->field('b.id,b.uip,b.userid,b.moneynum,r.tit,b.sj,r.id as rid,i.del_time,r.type,i.dtype,i.hz')
                        ->find();
                    if(empty($info)){
                        $this->error('域名:'.$v['tit'].'已经被审核！');
                    }
                    $op = new Reserveop();
                    if($v['num'] > 1){
                        $op->betchReserve($row,$info,$auction,$this->auth->id); 
                    }else{
                        $op->singleReserve($row,$info,$this->auth->id);
                    }
                    break;
                }
            }
            //直接存入redis
            if(!empty($saveRedis)){
                $op = new Reserveop();
                $row['status'] == 3 ? $op->savaRedis(3,$saveRedis,$this->auth->id) : $op->savaRedis($row,$saveRedis,$this->auth->id);
            }

            if($beerr){ //多个订单处理失败
                $this->sigleOrderOperate($beerr,$sj);
            }
            if($batch){ //多个订单处理成功
                $this->betchOrderOperate($batch,$sj,$auction,$row);
            }
            $msg = array_diff($olddomains,$domains);
            if($msg){
                $msg =  implode(',',$msg);
                $this->success('操作成功！'.$msg.'域名不在库中或状态错误,已为您过滤！');
            }
            $this->success('操作成功！');
        }
        $ids = $this->request->get('id');
        $domains = $this->model->whereIn('id',$ids)->group('tit')->column('tit');
        // 获取注册商列表
        $list = Db::name('category')->field('id,name')->where(['type'=>'api','status'=>'normal'])->whereIn('id',[66,77])->select();
        $this->view->assign(['zcs' => $list,'domains' => $domains]);
        return $this->view->fetch();
    }
    
    /**
     * 多个订单 失败处理
     */
    public function sigleOrderOperate($beerr,$sj){
        $smlerr = []; // 发邮件
        $info = $this->model->alias('r')->join('domain_baomoneyrecord b','r.id=b.infoid')->join('domain_user u','u.id=b.userid')
            ->whereIn('r.tit',$beerr)->where(['b.type' => 5,'b.status' => 0])
            ->field('b.userid,b.moneynum,r.id as rid,b.id,r.tit,u.uid')
            ->select();

        foreach($info as $v){
            Fun::ini()->lockBaoMoney($v['userid']) || $this->error('系统繁忙,请稍后操作!'.$v['tit']);
            Db::startTrans();
            //退回保证金
            $m1 = Db::name('domain_user')->where('id',$v['userid'])->setDec('baomoney1',$v['moneynum']);
            //还原保证金
            $m2 = Db::name('domain_baomoneyrecord')->where('id',$v['id'])->update(['status' => 2,'otime' => $sj ,'sremark' => '域名:'.$v['tit'].' 预定失败' ]);
            //更改状态
            $m3 = $this->model->where('id',$v['rid'])->update(['status' => 3, 'endtime' => $sj,'admin_id' => $this->auth->id]);
            $m4 = Db::name('domain_order_reserve_info')->where(['tit' => $v['tit'],'status' => 0,'type' => 0])->setField('status',3);
            if($m1 == $m2 && $m3 && $m4){
                Db::commit();
                $smlerr[$v['userid']][] = ['userid' => $v['userid'],'tit' => $v['tit']];
            }else{
                Db::rollback();
            }
            Fun::ini()->unlockBaoMoney($v['userid']);
        }
        // 发邮件
        $this->sendmail($smlerr,3);
    }

    /**
     * 批量预定成功处理
     */
    public function betchOrderOperate($batch,$sj,$auction,$row){

        if(empty($auction['money'])){
            $this->result('',300,'检测到域名有多人预定,请输入竞拍参数!');
        }
        $hours = date('H');
        // 定义时间基点
        $auction['start_time'] = $sj;
        if($hours < 19){
            $endtime = strtotime(date('Y-m-d 19:00:00'));
        }else{
            $endtime = strtotime('+1 day '.date('Y-m-d 19:00:00'));
        }
        $info = $this->model->alias('r')->join('domain_baomoneyrecord b','r.id=b.infoid')->join('domain_order_reserve_info i','i.tit=r.tit')->join('domain_user u','u.id=b.userid')
            ->whereIn('r.tit',$batch)->where(['b.type' => 5,'b.status' => 0])
            ->field('b.moneynum,b.userid,r.tit,i.hz,i.dtype,u.uid')
            ->group('r.tit')
            ->select();
        $smdInfo = [];
        $redis = new Redis(['select' => 2]);
        foreach ($info as $k => $v) {
            $orderInfo = $this->model->where(['tit' => $v['tit']])->field('id,userid')->order('time desc')->select();
            $auction['tit'] = $v['tit'];
            $auction['hz'] = $v['hz'];
            $auction['dtype'] = $v['dtype'];
            $auction['cur_money'] = $v['moneynum'];
            $olen = count($orderInfo);
            $auction['lx_userid'] = $orderInfo[$olen - 1]['userid'];
            $auctionEndtime = $redis->get('auction_endtime');
            if(empty($auctionEndtime) || date('H', $auctionEndtime) == 21){
                $auction['end_time'] = $endtime;
                $redis->set('auction_endtime',$endtime);
            }else{
                $endtimeTen = $auctionEndtime + 600;
                $auction['end_time'] = $endtimeTen;
                $redis->set('auction_endtime',$endtimeTen);
            }
            if($redis->ttl('auction_endtime') <= 0){
                // 时间戳到期时间 晚上7点
                $tomore = $endtime - $sj;
                $redis->expire('auction_endtime',$tomore);
            }
            //多条记录
            Db::startTrans();
            //插入竞拍参数
            $i1 = Db::name('domain_auction_info')->insertGetId($auction);
            //添加竞拍记录
            $record = [];
            foreach($orderInfo as  $vv){
                $record[] = ['money' => 0, 'auction_id' => $i1, 'time' => $sj, 'type' => 4, 'userid' => $vv['userid'], 'res_money' => $v['moneynum']];
            }
            $i2 = Db::name('domain_auction_record')->insertAll($record);
            //更改订单状态为竞拍 --
            $u1 = $this->model->whereIn('id',array_column($orderInfo,'id'))->update(['status' => 2,'auction_id' => $i1,'admin_id' => $this->auth->id]);
            $u2 = Db::name('domain_order_reserve_info')->where('tit' , $v['tit'])->setField('status',2);
            if($i1 && $i2 && $u1 && $u2){
                Db::commit();
                $smdInfo[$v['uid']][] = ['userid' => $v['userid'],'tit' => $v['tit'],'start_time' => $auction['start_time'],'end_time' => $auction['end_time'] ];
                $redis->lpush('auction_domain_info',$i1);
                $redis->hMset('reserve_domain_'.$v['tit'],$row);
            }else{
                Db::rollback();
            }
        }
        // 发邮件
        $this->sendmail($smdInfo,2);
    }
    /**
     * 发送邮件处理
     */
    public function sendmail($domains,$status){
        $sendMail = new sendMail();
        foreach($domains as $k => $v){
            $sendMail -> betchDomainReserve($k,$v,$status);
        }
    }

    /**
     * 已提交的阿里云域名设置可预定状态
     */

    private function setLoadDomainStatus($data){
        global $reserve_db;
        
        $ldb = Db::connect($reserve_db);
       
        $hours = date('YmdH',strtotime('+1 hours'));
        $tablename = 'domain_pro_reserve_'.$hours;

        //查询表是否存在
        $flag = $ldb->query('SHOW TABLES LIKE "'.PREFIX.$tablename.'"');
        
        if(empty($flag)){
            return false;
        }

        $tits = array_keys($data);

        $ntime = strtotime('-3 month');

        Db::startTrans();
        try{
            $ldb->name($tablename)->whereIn('tit',$tits)->setField('status',0);
            Db::name('domain_order_reserve_info')->whereIn('tit',$tits)->where('time','>',$ntime)->setField('status',0);
        }catch(\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
        $redis = new Redis(['select' => 5,'host' => '47.105.129.157','port' => 6379,'password' => 'SrhWdK1J5t','persistent' => false]);
        $sj = time();
        
        foreach($data as $k => $v){
            $expire = $v - $sj;
            $redis->set('reserve_domain_modi_status_'.$k,$k,$expire);
        }
        Db::commit();
        return true;
        
    }


}
