<?php

namespace app\admin\controller\domain\violation;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 手动添加预处罚域名
 */
class Manual extends Backend
{
    private $connect = null;

    public function _initialize()
    {
        global $violation_db;
        $this->connect = Db::connect($violation_db);
        parent::_initialize();
    }

    //添加违规域名
    public function index()
    {
        if ($this->request->isPost()) {

            $params = $this->request->post();

            if(empty($params['punish_type']) && $params['punish_type'] !== '0'){
                $this->error('请选择处罚动作');
            }
            if(empty($params['type_cause']) && $params['type_cause'] !== '0'){
                $this->error('请选择处罚原因');
            }
            if(empty($params['ttxt'])){
                $this->error('请输入要处罚的域名');
            }
            $domains = Fun::ini()->moreRow($params['ttxt']);

            if(count($domains) > 1000){
                $this->error('每次最多提交1000个域名');
            }

            $pinfo = Db::name('domain_pro_n')->alias('n')->join('domain_user u','u.id=n.userid')
                ->field('n.tit,n.userid,u.uid')->whereIn('n.tit',$domains)
                ->select();
            if(empty($pinfo)){
                $this->error('域名不存在库中');
            }

            if(empty($params['add_type'])){
                $exists = $this->connect->name('domain_violation')->where('reported_status',0)->whereIn('tit',$domains)->count();
                if($exists){
                    $this->error('该批域名包含未上报域名,请确认!');
                }
                $lexists = $this->connect->name('domain_violation_oneself')->whereIn('tit',$domains)->count();
                if($lexists){
                    $this->error('该批部分域名已存在自查列表,请去自查列表处理!');
                }

                $reported = 0;
            }else{
                //获取图片路径
                $imgPaths = $this->connect->name('domain_violation_oneself')->whereIn('tit',array_column($pinfo,'tit'))->column('img_path','tit');
                $reported = 1;
            }

            $userPro = [];
            foreach($pinfo as $v){
                $userPro[$v['userid']][] = $v;
            }
            $time = time();
            $insertPunish = [];

            foreach($userPro as $k => $v){
                foreach($v as $vv){
                    $insertPunish[] = [
                        'tit' => $vv['tit'],
                        'create_time' => $time,
                        'punish_type' => $params['punish_type'],
                        'type_cause' => $params['type_cause'],
                        'status' => 0,
                        'userid' => $k,
                        'uid' => $vv['uid'],
                        'reported_status' => $reported,
                        'add_type' => $params['add_type'],
                        'img_path' => empty($imgPaths[$vv['tit']]) ? '' : $imgPaths[$vv['tit']],
                    ];
                }
            }

            $insertPunishs = array_chunk($insertPunish,500);

            $this->connect->startTrans();
            try{
                //插入违规域名
                foreach($insertPunishs as $v){
                    $this->connect->name('domain_violation')->insertAll($v);
                }

                //如果是自查域名就删除
                if($reported == 1){
                    $tits = array_chunk($domains,500);
                    foreach($tits as $v){
                        $this->connect->name('domain_violation_oneself')->whereIn('tit',$v)->delete();
                    }
                    //获取违规域名的解析记录并插入
                    foreach($userPro as $k => $v){
                        $this->indeserDomainRecord($k,array_column($v,'tit'));
                    }
                }

            }catch(Exception $e){
                $this->connect->rollback();
                $this->error($e->getMessage());
            }

            $this->connect->commit();

            $this->success('提交成功','reload');

        }

        //自查传过来id
        $ids = $this->request->param('ids');
        $tits = $this->connect->name('domain_violation_oneself')->whereIn('id',$ids)->limit(1000)->column('tit');
        $this->view->assign('tits',$tits);
        return $this->view->fetch();
    }

    //根据域名和userid 插入解析记录
    private function indeserDomainRecord($userid,$tits){

        $recordCount = Db::name('action_record')->where('userid',$userid)->whereIn('tit',$tits)->count();

        if($recordCount){
            //500分页
            $page = ceil($recordCount / 500);

            for($i = 0;$i < $page;$i++){
                $offset = $i * 500;
                $insertRecord = Db::name('action_record')
                    ->field('remark,newstime,uip,userid,tit')
                    ->whereIn('tit',$tits)
                    ->limit($offset,500)
                    ->select();
                $this->connect->name('domain_record_violation')->whereIn('tit',$tits)->delete();
                $this->connect->name('domain_record_violation')->insertAll($insertRecord);
            }

        }

    }


}
