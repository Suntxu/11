<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\commin\Fun;
use app\admin\library\AliyunApi;
use app\admin\library\WestApi;
/**
 * 域名批量解析记录
 *
 * @icon fa fa-user
 */
class Parserecord extends Backend
{

    protected $model = null;
    private $usable = [66,67,68,71,72,73,74,75]; //解析可用的注册商接口
    protected $noNeedRight = ['aliParseStatusModi','westParseStatusModi','verifyinfo'];
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_record');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $filter = $this->request->param('filter');
            if($filter == '{}'){
                $total = $this->model->count();
            }else{
                $total = $this->model->alias('r')->join('domain_pro_n p','r.tit=p.tit','left')->join('domain_user u','r.userid=u.id')
                    ->where($where)->count();
            }

            $list = $this->model->alias('r')->join('domain_pro_n p','r.tit=p.tit','left')->join('domain_user u','r.userid=u.id')
                         ->field('p.zcs,u.uid,r.RecordId,r.RR,r.Type,r.Value,r.TTL,r.Priority,r.Line,r.Status,r.Weight,r.time,r.tit,r.id')
                         ->where($where)->order($sort,$order)->limit($offset, $limit)
                         ->select();
            $cates = $this->getCates();
            foreach($list as &$v){
                $v['u.uid'] = $v['uid'];
                $v['r.tit'] = $v['tit'];
                $v['r.time'] = $v['time'];
                $v['r.Type'] = $v['Type'];
                $v['zcs'] = empty($cates[$v['zcs']]) ? '--' : $cates[$v['zcs']];
                if($v['Status'] == 'Enable'){
                    $v['r.Status'] = '启动';    
                }else{
                    $v['r.Status'] = '停止';
                }
                if($v['Status'] == 'Disable'){
                    $v['r.Status'] = '<span style="color:red">停止</span>';
                }else{
                    $v['r.Status'] = '<span style="color:green">启用</span>';
                }

               
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 修改状态
     */
    public function modi(){
        if($this->request->isAjax()){
            $id = $this->request->get('id');
            $status = $this->request->get('status');
            if(!intval($id) || !(intval($status) && in_array($status,[1,2]))){
                return ['code' => 0, 'msg' => '参数错误'];
            }
            $status = $status == 1 ? 'Disable' : 'Enable';
            $info = $this->model->alias('r')->join('domain_pro_n t','r.tit=t.tit','left')
                    ->field('t.tit,t.zcs,r.id,r.Status,t.infoid,t.id,t.infoZR,t.api_id,r.RecordId,r.id as rid,r.Type,r.RR,r.Value,r.Line,r.TTL,r.userid')
                    ->where('r.id',$id)
                    ->find();
            if(empty($info)){
                return ['code' => 0,'msg' => 'ID'.$id.' 未找到记录'];
            }

            //匹配可用注册商
            if(!in_array($info['zcs'],$this->usable)){
                return ['code' => 0,'msg' => '该注册商暂不支持此操作'];
            }

            if($status == $info['Status']){
                return ['code' => 1,'msg' => '操作成功'];
            }

            //判断过户状态
            $temStatus = $this->verifyinfo($info); 
            if(empty($temStatus)){
                return $temStatus;
            }

            // 判断注册商 -- 西数独自调用接口  其他用阿里云接口
            if($info['zcs'] == 67){
                $res = $this->westParseStatusModi($info,$status);
            }else{
                $res = $this->aliParseStatusModi($info,$status);
            }
            return $res;
        }

    }

    /**
     * 阿里云解析状态改变
     */
    private function aliParseStatusModi($info,$status){

        //查询api
        $apiInfo = Db::name('domain_api')->where('id',$info['api_id'])->field('accessKey,secret')->find();
        if(empty($apiInfo)){
            return ['code' => 0,'msg' => 'api不存在,请确认!'];
        }
        $domainApi = new AliyunApi($info['zcs'],$apiInfo['accessKey'],$apiInfo['secret']);
        try{
            $result = $domainApi->setDomainStatus($info['RecordId'],$status);
        }catch (\Exception $e) {
            return ['code' => 0,'msg' => $e->getMessage()];            
        }
        if($result['status'] != 400){
            $t = $status == 'Enable'?'启用':'暂停';
            Db::name('domain_record')->where(['id' => $info['rid']])->update(['Status' => $status]);
            $remark = $t.'记录成功'.$info['Type'].'记录 '.$info['RR'].' '.Fun::ini()->getLinestr($info['Line']).' '.$info['Value'].' （TTL：'.$info['TTL'].'）';
            Db::name('action_record')->insert(['tit' => $info['tit'],'remark' => $remark,'stauts' => 1,'newstime' => time(),'uip' => $this->request->ip(),'userid' => $info['userid']]);
            return ['code' => 1,'msg' => $t.'记录成功'];
        }
        $msg = json_decode($result['msg'],true);
        return ['code' => 0,'msg' => $msg['Message']];

    }
    /**
     * 西部数据解析状态改变
     */
    private function westParseStatusModi($info,$status){

        //查询api
        $apiInfo = Db::name('domain_api')->where('id',$info['api_id'])->field('accessKey,secret')->find();
        if(empty($apiInfo)){
            return ['code' => 0,'msg' => 'api不存在,请确认!'];
        }
        $westApi = new WestApi($apiInfo['accessKey'],$apiInfo['secret']);
        $westApi->cmd = "dnsresolve\r\nstatus\r\nentityname:dnsrecord\r\n";
        $fstatus = $status == 'Enable' ? 0 : 1;
        $west_data = ['domain'=>$info['tit'],'rr_id'=>$info['RecordId'],'value'=>$fstatus];
        try{
            $result = $westApi->westapi($west_data);
        }
        catch (\Exception $e) {
            return ['code' => 0,'msg' => $e->getMessage()]; 
        }
        if($result['returncode'] != 200)
            return ['code' => 0,'msg' => $result['returnmsg']];

        $t = $status == 0?'启用':'暂停';
        Db::name('domain_record')->where(['id' => $info['rid']])->update(['Status' => $status]);
        $remark = $t.'记录成功'.$info['Type'].'记录 '.$info['RR'].' '.Fun::ini()->getLinestr($info['Line']).' '.$info['Value'].' （TTL：'.$info['TTL'].'）';
        Db::name('action_record')->insert(['tit' => $info['tit'],'remark' => $remark,'stauts' => 1,'newstime' => time(),'uip' => $this->request->ip(),'userid' => $info['userid']]);
        return ['code' => 1,'type' => 2,'msg' => $t.'记录成功'];
    }

    /*
    查询域名过户状态
    */
    private function verifyinfo($domain_info){
        if($domain_info['infoid'] <= 0)
            return ['code' => 0,'msg' => '域名还未过户信息模板'];
        elseif($domain_info['infoZR'] == 0)
            return ['code' => 0,'msg' => '域名未过户'];
        elseif($domain_info['infoZR'] == 2)
            return ['code' => 0,'msg' => '域名模板过户失败'];
        elseif($domain_info['infoZR'] == 3)
            return ['code' => 0,'msg' => '模板过户执行中'];

        $tem_info = Db::table(PREFIX.'domain_infoTemplate')->where(['id' => $domain_info['infoid'],'userid' => $domain_info['userid']])->field('id')->find();
        if(!$tem_info){
            Db::name('domain_pro_n')->where('id = '.$domain_info['id'])->update(['infoid' => 0,'infoZR' => 0]);
            return ['code' => 0,'msg' => '此域名信息模板不存在，请确认'];
        }
        return true;
    }


}
