<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\library\Redis;
use app\admin\common\Fun;
use app\admin\library\Workertask;

/**
 * 怀米hold
 */
class Hmhold extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        if ($this->request->isPost()) {
            global $remodi_db;

            $domains = $this->request->post('domains');
            if(empty($domains)){
                $this->error('域名不能为空');
            }

            $domains = Fun::ini()->moreRow($domains);

            if(count($domains) > 500){
                $this->error('一批最多可输入500个域名');
            }

            // 过滤数据库中的域名
            $domainList = Db::name('domain_pro_n')->field('zcs,api_id,tit,infoid,infoZR,userid')->whereIn('tit',$domains)->select();
            if(empty($domainList)){
                $this->error('请输入域名库存在的域名!');
            }
            $ll = Db::connect($remodi_db)->name('domain_hold_record')->whereIn('tit',array_column($domainList,'tit'))->where('status',0)->column('tit');
            if($ll){
                $this->error(implode(',',$ll).' 域名已提交hold ');
            }
            $apis = $this->getApis(-1);
            $ktit = [];
            $reg_domain = [];
            $msg = '';
            foreach($domainList as $v){
                if($v['infoid'] == '0'){
                    $msg .= $v['tit'].' 域名未过户;';
                    continue;
                }
                $api_info = $apis[$v['api_id']];
                if(!$api_info){
                    $this->error('接口商:'.$v['api_id'].' 信息获取失败');
                    break;
                }
                $ktit[$v['api_id']][] = $v['tit'];
                $reg_domain[$v['zcs']][$v['api_id']][] = $v['tit'];
            }
            if($msg){
                $this->error(rtrim($msg,';'));
            }
            $redis = new Redis();

            $tits = array_column($domainList,'tit');
            $tiaw = array_combine($tits,array_column($domainList,'userid'));

            $time = time();
            Db::startTrans();

            Db::name('domain_pro_n')->whereIn('tit',$tits)->setField('status',4);
            Db::name('domain_pro_trade')->whereIn('tit',$tits)->setField('lock',4);

            $main_task = ['createtime' => $time,'userid' => '-'.$this->auth->id,'tasktype' => 3,'remark' => json_encode(['ns1.domains-hold.com','ns2.domains-hold.com']),'dcount' => count($domainList),'uip' => '127.0.0.1'];

            $main_id = Db::table(PREFIX.'Task_record')->insertGetId($main_task);
            $sub_task = [];
            $inserts = [];
            foreach($ktit as $ak => $av){
                foreach($av as $v){
                    $sub_task[] = ['taskid' => $main_id,'tit' => $v,'userid' => '-'.$this->auth->id,'api_id' => $ak];
                    $inserts[] = ['tit' => $v,'taskid' => $main_id,'create_time' => $time,'status' => 0,'userid' => $tiaw[$v],'admin_id' => $this->auth->id];
                }
                $redis->RPush('info_task_all_api_'.$main_id,$ak);
            }
            $worker = new Workertask($redis);
            foreach($reg_domain as $k_reg => $v_reg){//根据注册商插入任务
                $batch_info = ['domain' => json_encode($v_reg),'userid' => '-'.$this->auth->id,'uid' => $this->auth->nickname,'dns' => $main_task['remark']];
                $worker->dnsmodification_work($main_id,$batch_info,$k_reg);
            }
            $redis->RPush('going_dns_id',$main_id);//进行中任务存入redis
            Db::table(PREFIX.'Task_Detail_3')->insertAll($sub_task);
            Db::connect($remodi_db)->name('domain_hold_record')->insertAll($inserts);

            Db::commit();

            $this->success('提交成功','reload');
        }

        return $this->view->fetch();
    }


}
