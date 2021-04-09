<?php

namespace app\admin\controller\domain\violation;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use think\Exception;
use app\admin\library\OssApi;


/**
 * 上报违规域名管理
 */
class Appear extends Backend
{
    private $connect = null;

    protected $noNeedRight = ['Updateillegal'];

    public function _initialize()
    {
        global $violation_db;
        $this->connect = Db::connect($violation_db);
        parent::_initialize();
    }
    /**
     * 未上报域名列表
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total =  $this->connect->name('domain_violation')->where($where)->where('reported_status',0)->count();

            $list = $this->connect->name('domain_violation')
                    ->field('id,tit,create_time,punish_type,type_cause,uid,img_path,add_type')
                    ->where($where)->where('reported_status',0)
                    ->order($sort, $order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();
            foreach($list as &$v){
                $v['punish_type'] = $fun->getStatus($v['punish_type'],['<span style="color: red;">hold</span>','<span style="color: orange;">警告</span>']);
                $v['type_cause'] = $fun->getStatus($v['type_cause'],[
                    '<span style="color: red;">网站存在欺诈侵权类违法违规内容</span>',
                    '<span style="color: orange;">网站存在赌博类违法违规内容</span>',
                    '<span style="color: yellowgreen;">网站存在色情低俗类违法违规内容</span>',
                    '<span style="color: deeppink;">网站存在国家政策类违法违规内容</span>',
                ]);
                if (empty($v['img_path'])) {
                    $v['img_path'] = 0;
                }
//                $v['add_type'] = $fun->getStatus($v['add_type'],['手动','<span style="color: gray;">自查</span>']);

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 已上报域名列表
     */
    public function reported()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total =  $this->connect->name('domain_violation')->where($where)->where('reported_status',1)->count();

            $list = $this->connect->name('domain_violation')->where('reported_status',1)
                ->field('id,tit,create_time,punish_type,type_cause,uid,img_path,add_type')
                ->where($where)
                ->order($sort, $order)->limit($offset, $limit)
                ->select();

            $fun = Fun::ini();
            foreach($list as &$v){
                $v['punish_type'] = $fun->getStatus($v['punish_type'],['<span style="color: red;">hold</span>','<span style="color: orange;">警告</span>']);
                $v['type_cause'] = $fun->getStatus($v['type_cause'],[
                    '<span style="color: red;">网站存在欺诈侵权类违法违规内容</span>',
                    '<span style="color: orange;">网站存在赌博类违法违规内容</span>',
                    '<span style="color: yellowgreen;">网站存在色情低俗类违法违规内容</span>',
                    '<span style="color: deeppink;">网站存在国家政策类违法违规内容</span>',
                ]);
                $v['add_type'] = $fun->getStatus($v['add_type'],['手动','<span style="color: gray;">自查</span>']);
                if (empty($v['img_path'])) {
                    $v['img_path'] = 0;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 上报
     */
    public function updamo(){
        if($this->request->isAjax()){
            $ids = $this->request->post('ids');

            if(empty($ids)){
                $this->error('缺少重要参数');
            }
            $ids = explode(',',$ids);

            $info = $this->connect->name('domain_violation')->field('userid,tit')->where('reported_status',0)->whereIn('id',$ids)->select();
            if(empty($info)){
                $this->error('未上报域名不存在');
            }
            $userPro = [];
            foreach($info as $v){
                $userPro[$v['userid']][] = $v['tit'];
            }

            $this->connect->startTrans();

            try{

                $this->connect->name('domain_violation')->whereIn('id',$ids)->update(['reported_status' => 1,'create_time' => time()]);
                foreach($userPro as $k => $v){
                    $this->indeserDomainRecord($k,$v);
                }

            }catch(Exception $e){
                $this->connect->rollback();
                $this->error($e->getMessage());
            }
            $this->connect->commit();
            $this->success('操作成功');

        }
    }

    /**
     * 取消 自查列表
     */
    public function del($ids=null){

        if($this->request->isAjax()){
            $paths = $this->connect->name('domain_violation')->where('img_path != ""')->whereIn('id',$ids)->column('img_path');
            $this->connect->name('domain_violation')->whereIn('id',$ids)->delete();
            $OssBecket = new OssApi();
            $adr = str_replace('/operate','',IMGURL_OPERATE);
            foreach($paths as $v){
                $path = ltrim(str_replace($adr,'',$v),'/');
                $OssBecket->deleteField(OSS_BUCKET_NAME,$path);
            }

            $this->success('删除成功');

        }

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

    //修改违规图片
    public function Updateillegal(){
        if($this->request->isAjax()){
            $info = $this->request->post();
            $img = IMGURL_OPERATE.$info['img']['data']['url'];
            $this->connect->name('domain_violation')->whereIn('id',$info['ids'])->setField(['img_path' => $img]);
            $this->success('修改成功');

        }
    }

}
