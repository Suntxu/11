<?php

namespace app\admin\controller\domain\reserve;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
use think\Config;

/**
 * 多通道预定处理
 * @icon fa fa-user
 */
class Multop extends Backend
{
    
    private $redis = null;

    protected $noNeedRight = ['getZcs'];

    public function _initialize(){
        
        $this->redis = new Redis(['select' => 6]);

        parent::_initialize();

    }
    
    /**
     * 待提交
     */
    public function index(){
        
        if ($this->request->isAjax()){

            $list = [];
            
            $i = 1;
            $tits = [];

            $regid = Config::get('mult_domain_reserve_zcs_id');
            $regid = array_keys($regid);
            list($tit,$api_id,$del_time,$offset,$limit) = $this->searchTop();

            foreach($regid as $v){

                $task = $this->redis->lrange('reg_going_reserve_'.$v,0,-1);

                foreach($task as $vv){
                    
                    $data = $this->redis->hgetall('reg_going_reserve_'.$v.'_'.$vv);
                    
                    if($data){

                        $domain = json_decode($data['domain'],true);
                        
                        $api = json_decode($data['api'],true);
                        $subtime = empty($data['createtime']) ? '' : $data['createtime'];
                        foreach($domain as $info){

                            $skey = $info['id'].'_'.$api['id'];
                            if(isset($tits[$skey])){  //同一个注册商只显示一个域名
                                continue;
                            }
                            
                            //判断是否已经处理
                            $opk = $this->redis->get('book_submitsuccess_'.$api['id'].'_'.$info['tit']);
                            if($opk){
                                continue;
                            }
                            if($tit && !in_array($info['tit'],$tit)){
                                continue;
                            }
                            if($api_id && $api_id != $api['id']){
                                continue;
                            }
                            
                            if($del_time){
                                if(($del_time[0] && $del_time[1]) && ($del_time[0] > $info['del_time'] || $del_time[1] < $info['del_time'])){
                                    continue;
                                }elseif($del_time[0] && $del_time[0] > $info['del_time']){
                                    continue;
                                }elseif($del_time[1] && $del_time[1] < $info['del_time']){
                                    continue;
                                }
                            }
                            $info['regname'] = $api['regname'];
                            $info['apiname'] = $api['tit'];
                            $info['pid'] = $v.'_'.$vv.'_'.$info['id'];
                            $info['increase'] = $i;
                            $info['subtime'] = $subtime;
                            $list[] = $info;
                            $i++;
                            $tits[$skey] = 1;

                            //将pid和信息存入无序集合
                            $sm = $info['tit'].'_'.$api['id'].'|'.$info['pid'];    
                            $this->redis->sadd('multire_reserve_submit_id',$sm);
                        }
                    }
                    
                }
            }

            $total = count($list);
            return json(['total'=>$total,'rows'=> array_slice($list,$offset,$limit) ]);
        }
        return $this->view->fetch();

    }
    
    /**
     * 待处理
     */
    public function execdomain (){
        
        if ($this->request->isAjax()){
            
            $list = [];
            $i = 1;
            $tits = [];
            $task = $this->redis->lrange('book_hander_submitsuccess',0,-1);
            $apis = $this->getApis(-1);
            
            list($tit,$api_id,$del_time,$offset,$limit) = $this->searchTop();
            $this->redis = new Redis(['select' => 6]); //重新选择库

            foreach($task as $v){

                $ikeys = explode('_',$v);
                $skey = $ikeys[0].'_'.$ikeys[1];
                if(isset($tits[$skey])){  //同一个注册商只显示一个域名
                    continue;
                }
                //判断是否已经处理
                $ofied = $this->redis->get('book_handersuccess_'.$ikeys[1].'_'.$ikeys[0]);
                // echo 'book_handersuccess_'.$ikeys[1].'_'.$ikeys[0];die;
                // book_handersuccess_21_baidu.com
                if($ofied){
                    continue;
                }
                if($tit && !in_array($ikeys[0],$tit)){
                    continue;
                }
                if($api_id && $api_id != $ikeys[1]){
                    continue;
                }
                if($del_time){
                    if(($del_time[0] && $del_time[1]) && ($del_time[0] > $ikeys[2] || $del_time[1] < $ikeys[2])){
                        continue;
                    }elseif($del_time[0] && $del_time[0] > $ikeys[2]){
                        continue;
                    }elseif($del_time[1] && $del_time[1] < $ikeys[2]){
                        continue;
                    }
                }
                
                if(isset($apis[$ikeys[1]])){

                    $regname = $apis[$ikeys[1]]['regname'];

                    $apiname = $apis[$ikeys[1]]['tit'];

                }else{

                    $regname = '';

                    $apiname = '';

                }
                $list[] = [
                    'increase' => $i,
                    'tit' => $ikeys[0],
                    'regname' => $regname,
                    'apiname' => $apiname,
                    'del_time' => empty($ikeys[2]) ? '' : $ikeys[2],
                    'pid' => $ikeys[0].'_'.$ikeys[1],
                ];
                $i++;
                $tits[$skey] = 1;
            }

            $total = count($list);

            return json(['total'=>$total,'rows'=> array_slice($list,$offset,$limit) ]);
        }
        return $this->view->fetch();

    }
    /**
     * 批量提交并修改状态
     */
    public function multire(){

        if($this->request->isAjax()){
            $param = $this->request->post('row/a');
            
            if(empty($param['flag']) || !in_array($param['flag'],[1,2,3,4]) || empty($param['domain']) ){
                $this->error('必填项不能为空');
            }
            $domain = Fun::ini()->moreRow($param['domain']);
            
            if(count($domain) > 500){
                $this->error('一次最多提交500个域名');
            }
            $tdomain = $domain;
            if(empty($param['id'])){ //获取域名id
                if(empty($param['api_id'])){
                    $this->error('请选择接口商');
                }
                //处理数组
                foreach($domain as &$v){
                    $v = $v.'_'.$param['api_id'];
                }
                $ids = $this->getDomainId($domain,$param['flag']);
            }else{
                $ids = explode(',',$param['id']);
            }
            $data = [];
            $time = time();
            //检测 
            foreach($ids as $k => $v){
                $res = $this->checkDomainStatus(['id' => $v,'flag' => $param['flag'],'time' => $time ]);
                if(is_array($res)){
                    $data[] = $res;    
                }
            }

            //预定记录
            $record = [];
            $tits = [];
            
            foreach($data as $v){

                if($param['flag'] == 2){
                    $this->redis->set($v['key'],$v['status'],18000);
                }else{
                    $this->redis->set($v['key'],$v['status'],518400);
                    //释放集合
                    if($param['flag'] != 3){
                        $this->redis->srem('multire_reserve_submit_id',$v['kid']);
                    }
                }
                $tits[] = $v['tit'];
                $record[] = [
                    'tit' => $v['tit'],
                    'del_time' => $v['del_time'],
                    'api_id' => $v['api_id'],
                    'admin_id' => $this->auth->id,
                    'create_time' => $time,
                    'status' => $param['flag'],  
                ];
            }    
            
            if(empty($tits)){
                $this->error('该批域名全部未到删除时间或者状态错误,请确认！');
            }
            $tis = array_diff($tdomain,$tits);
            $msg = '';
            if($tis){
                $msg = ',以下域名未到过期时间或者状态错误,已为您过滤:'.implode(',',$tis);
            }
            //插入操作记录
            Db::name('domain_multi_reserve_record')->insertAll($record);

            $this->success('处理成功'.$msg);

        }
        $param = $this->request->get();
        $data = [];
        $id = [];
        if($param['ids']){
            $ids = explode(',',$param['ids']);
            foreach($ids as $k => $v){
                if($param['status'] == 1){ //提交 66_313_7640
                    $data[] = $this->getDomainInfo(explode('_',$v)); 
                }else{ // 处理 baidu.com_33
                    $data[] = $this->getDomainCommit($v);
                }
                array_push($id,$v);
            }
            $data = array_column($data,'tit');
        }
        $zcs = Config::get('mult_domain_reserve_zcs_id');
        $this->assign([
            'tits' => $data,
            'ids' => implode(',',$id),
            'status' => $param['status'],
            'zcs' => $zcs,
            'zinfo' => json_encode($zcs),
        ]);

        return $this->fetch();

    }
    /**
     * 搜索方法
     */
    public function searchTop(){
        $param = $this->request->param();
        $where = json_decode($param['filter'],true);
        $tit = [];
        $api_id = 0;
        $del_time = [];
        if(isset($where['tit'])){//域名搜索

            $TextAv=str_replace("\r","",$where['tit']);
            $Text=preg_split("/\n/",$TextAv);
            $tit = preg_replace('/\s+/is','',array_filter($Text));
            if(count($tit) > 300){
                $this->error('文本域最多可搜索300行数据！');
            }
        }
        if(isset($where['regname'])){
            $regid = Config::get('mult_domain_reserve_zcs_id');
            $tinfo = $regid[intval($where['regname'])];
            $tinfo = array_keys($tinfo['api']);
            $api_id = $tinfo[0];
        }

        if(isset($where['del_time'])){
            $times = explode(' - ',$where['del_time']);
            $t1 = empty($times[0]) ? 0 : strtotime($times[0]);
            $t2 = empty($times[1]) ? 0 : strtotime($times[1]);
            $del_time = [$t1,$t2];

        }

        return [$tit,$api_id,$del_time,$param['offset'],$param['limit']];

    }


    /**
     * 获取设置的注册商
     */
    public function getZcs(){
        $arr = [];
        $regid = Config::get('mult_domain_reserve_zcs_id');
        foreach($regid as $k => $v){
            $arr[] = ['id' => $k,'name' => $v['name']];
        }
        return $arr;
    }
    /**
     * 单个修改redis状态
     */
    public function modi(){

        if($this->request->isAjax()){

            $param = $this->request->param();
            
            if(empty($param['flag']) || !in_array($param['flag'],[1,2,3,4]) || empty($param['id'])){
                $this->error('非法参数');
            }
            $param['time'] = time();
            // list($key,$status,$kid,$tit) = $this->checkDomainStatus($param);
            $res = $this->checkDomainStatus($param);
            if(!is_array($res)){
                $this->error($res);
            }
            if($param['flag'] == 2 ){
                $this->redis->set($res['key'],$res['status'],18000);
            }else{
                $this->redis->set($res['key'],$res['status'],518400);
            }
            //释放集合
            if($param['flag'] == 1 || $param['flag'] == 4){
                $this->redis->srem('multire_reserve_submit_id',$res['kid']);
            }

            //增加记录
            Db::name('domain_multi_reserve_record')->insert([
                'tit' => $res['tit'],
                'del_time' => $res['del_time'],
                'api_id' => $res['api_id'],
                'admin_id' => $this->auth->id,
                'create_time' => $param['time'],
                'status' => $param['flag'],
            ]);

            $this->success('操作成功');

        }

    }
    /**
     * 验证域名的状态  
     */
    private function checkDomainStatus($param){

        if($param['flag'] == 1 || $param['flag'] == 4){

            $ids = explode('_',$param['id']);

            if(count($ids) != 3){
                return '|非法的id参数'.$ids;
            }
            $data = $this->getDomainInfo($ids,true);
            $kid = $data['tit'].'_'.$data['api_id'].'|'.$param['id'];
            //判断是否已经处理
            $key = 'book_submitsuccess_'.$data['api_id'].'_'.$data['tit']; // 3 4+

        }else{
            $data = $this->getDomainCommit($param['id'],true);
            $kid = $param['id'];
            $key = 'book_handersuccess_'.$data['api_id'].'_'.$data['tit'];
        }
        
        if(empty($data)){
            return '|未知数据'.$ids;
        }

        $opk = $this->redis->get('book_submitsuccess_'.$data['api_id'].'_'.$data['tit']);
        $ofied = $this->redis->get('book_handersuccess_'.$data['api_id'].'_'.$data['tit']);

        if($param['flag'] == 1){ //提交
            if($opk || $ofied){
                return $data['tit'].'状态错误,已过滤！';
            }
            $status = 3;

        }elseif($param['flag'] == 2){ //成功
            if($opk != 3 || $ofied){
                return $data['tit'].'状态错误,已过滤！';
            }
            if($data['del_time'] > $param['time'] ){
                return $data['tit'].'未到域名删除时间,已过滤！';
            }
            $status = 1;
        }elseif($param['flag'] == 3) { //处理失败
            if($opk != 3 || $ofied){
                return $data['tit'].'状态错误,已过滤！';
            }
            $status = 2;

        }else{ //直接失败
            if(empty($opk) && empty($ofied)){ // 直接处理失败
                $status = 4;
            }else{
                return $data['tit'].'状态错误,已过滤！';
            }
        }
        return ['key' => $key,'status' => $status,'kid' => $kid,'tit' => $data['tit'],'del_time' => $data['del_time'],'api_id' => $data['api_id'] ];
    }

    /**
     * 根据域名获取id
     */
    private function getDomainId($domains,$flag){

        $ids = [];
        if($flag == 1 || $flag == 4){ //提交
            $scount = $this->redis->scard('multire_reserve_submit_id');
            if($scount > 0){
                $members = $this->redis->smembers('multire_reserve_submit_id');
                foreach($members as $v){
                    $sm = explode('|',$v);
                    if(in_array($sm[0], $domains)){
                        $ids[] = $sm[1];                        
                    }
                }
            }
        }else{ // 处理
            $task = $this->redis->lrange('book_hander_submitsuccess',0,-1);
            foreach($task as $v){
                $tits = explode('_', $v);
                if(in_array($tits[0].'_'.$tits[1], $domains)){
                    $ids[] = $tits[0].'_'.$tits[1];                        
                }
            }
        }
        return $ids;
    }
    /**
     * 根据id获取未提交域名信息
     */
    private function getDomainInfo($ids,$flag = false){

        $data = $this->redis->hgetall('reg_going_reserve_'.$ids[0].'_'.$ids[1]);

        if(!$data){
            if($flag){
                $this->error('哈希表无数据'.$ids);
            }else{
                return [];
            }
        }
        
        $domain = json_decode($data['domain'],true);
        
        $api = json_decode($data['api'],true);

        $info = [];

        foreach($domain as $v){

            if($v['id'] == $ids[2]){

                $v['api_id'] = $api['id'];
                $info = $v;
                break;
            }

        }

        return $info;
    }
    
    /**
     * 根据id获取已处理的域名信息
     */
    private function getDomainCommit($ids,$flag = false){
        $info = [];
        $task = $this->redis->lrange('book_hander_submitsuccess',0,-1);
        foreach($task as $v){
            $tis = explode('_', $v);
            if($tis[0].'_'.$tis[1] == $ids){
                $info['tit'] = $tis[0];
                $info['api_id'] = $tis[1];
                $info['del_time'] = empty($tis[2]) ? '' : $tis[2];
                break;
            }
        }
        if(empty($info) && $flag){
            $this->error('队列中无此值:'.$ids);
        }
        return $info;
    }

    /**
     * 临时删除redis数据
     */
    public function temp(){
        if($this->request->isAjax()){
            $time = strtotime(date('Y-m-d 23:59:59'));
            $task = $this->redis->lrange('book_hander_submitsuccess',0,-1);
            $this->redis = new Redis(['select' => 6]); //重新选择库
            foreach($task as $v){
                $ikeys = explode('_',$v);
                if(empty($ikeys[2]) || $ikeys[2] < $time){
                    $this->redis->lRem('book_hander_submitsuccess',0,$v);
                }
            }
            $this->success('删除成功');
        }
    }

}
