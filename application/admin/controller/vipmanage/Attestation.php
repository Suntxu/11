<?php

namespace app\admin\controller\vipmanage;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
use app\admin\library\AliyunApi;
use think\Config;
/**
 * 实名审核
 *
 * @icon fa fa-circle-o
 * @remark 主要用于管理上传到又拍云的数据或上传至本服务的上传数据
 */
class Attestation extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['alierr','slist'];

    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 查看
     */
     public function index()
     {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $def = '';
            if(!empty($group)){

                if((mb_strlen($group) > 5 && !strpos($group,'·')) || strpos($group,'公司')){
                    $def = ' re.busname like "%'.$group.'%" ';
                }else{
                    $x = mb_substr($group,0,1); //获取姓
                    $m = mb_substr($group,1);
                    $def = ' re.xing = "'.$x.'" ';
                    if(!empty($m)){
                        $def .= ' and re.ming like "'.$m.'%" ';
                    }
                }
            }
            $total = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
                    ->join('user_renzhengapi r','r.info_id=t.id','right')
                    ->join('domain_user u','r.userid=u.id','left')
                    ->join('domain_api a','r.api_id=a.id','left')
                    ->join('category c','c.id=a.regid','left')
                    ->join('user_renzheng re','t.renzheng_id=re.id','left')
                    ->where($where)->where($def)
                    ->count();
            $list = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
                    ->join('user_renzhengapi r','r.info_id=t.id','right')
                    ->join('domain_user u','r.userid=u.id','left')
                    ->join('domain_api a','r.api_id=a.id','left')
                    ->join('category c','c.id=a.regid','left')
                    ->join('user_renzheng re','t.renzheng_id=re.id','left')
                    ->field('r.system_id,t.id as tid,r.id,u.uid,a.tit,c.name,r.auth_status,r.info_status,r.auth_remark,r.info_remark,r.createtime,r.email_status,a.ifreal,t.title,info_id,t.RegistrantType,re.xing,re.ming,re.busname,r.api_id,c.id as cid')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $redis = new Redis();
            $fun = Fun::ini();

            foreach($list as $k=>$v){

                if( $v['info_status'] != 2 || !in_array($v['auth_status'],[0,1,4]) || $v['name'] == '商务中国' || $v['name'] == '35互联' || $v['name'] == '腾讯云' || $v['ifreal'] == 1){ 
                    $list[$k]['op'] = '--';
                }else if($v['cid'] != 107){
                    $url = '/admin/vipmanage/attestation/resetreal/ids/'.$v['id'];
                    $list[$k]['op'] = '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-success" title="重新实名">重新实名</button>&nbsp;';
                }

                if($v['name'] == 'Centralnic' || $v['name'] == 'RRP' || ($v['name'] == '商务中国'  &&  in_array($v['auth_status'],[0,1,4])  ) || (($v['name'] == '中资源' || $v['name'] == '35互联' || $v['name'] == '腾讯云' || $v['name'] == '190') && in_array($v['auth_status'],[1,4]) )  ){
                    $url = '/admin/vipmanage/attestation/del/ids/'.$v['id'];
                    if(empty($list[$k]['op']) || $list[$k]['op'] == '--'){
                        $list[$k]['op'] = '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-del btn-warning" title="删除">删除</button>&nbsp;';
                    }else{
                        $list[$k]['op'] .= '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-del btn-warning" title="删除">删除</button>&nbsp;';
                    }
                }
                if($v['cid'] == 107){
                    if( empty($list[$k]['op']) || $list[$k]['op'] == '--'){
                        $list[$k]['op'] = '<a href="/admin/vipmanage/attestation/slist/ids/'.$v['id'].'" class="btn btn-xs btn-warning dialogit" title="查看外部模板">查看</a>&nbsp;';
                    }else{
                        $list[$k]['op'] .= '<a href="/admin/vipmanage/attestation/slist/ids/'.$v['id'].'" class="btn btn-xs btn-warning dialogit" title="跳转">查看</a>&nbsp;';
                    }
                }

                

                if($v['auth_status'] != 3){
                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['id'].'&status='.$v['auth_status'].'&pstatus=3';
                    if( empty($list[$k]['op']) || $list[$k]['op'] == '--'){
                        $list[$k]['op'] = '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-success" title="实名成功">实名成功</button>&nbsp;';
                    }else{
                        $list[$k]['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-success" title="实名成功">实名成功</button>&nbsp;';
                    }
                    
                }

                if($v['auth_status'] != 1 && $v['auth_status'] != 4){

                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['id'].'&status='.$v['auth_status'].'&pstatus=4';
                    if( empty($list[$k]['op']) || $list[$k]['op'] == '--'){
                        $list[$k]['op'] = '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-danger" title="实名失败">实名失败</button>&nbsp;';
                    }else{
                        $list[$k]['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-danger" title="实名失败">实名失败</button>&nbsp;';
                    }
                    
                }

                $list[$k]['auth_status'] = $fun->getStatus($v['auth_status'],['<font color="#e74c3c">未实名</font>','<font color="#e74c3c">实名提交失败</font>','<font color="#3498db">提交成功</font>','<font color="#18bc9c">认证成功</font>','<font color="#e74c3c">注册商实名失败</font>',9=>'<font color="#e74c3c">实名查询结果时模板不存在</font>']);
              
                $list[$k]['info_status'] =  $fun->getStatus($v['info_status'],[1=>'创建失败','创建成功',9=>'申请手动添加']);
                $list[$k]['email_status'] =  $fun->getStatus($v['email_status'],['未认证',2=>'已认证']);
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['c.id'] = $v['name'];
                $list[$k]['a.id'] = $v['tit'];
                $list[$k]['t.id'] = $v['tid'];
                $list[$k]['t.title'] = $v['title'];
                if($v['RegistrantType'] == 1){
                    $list[$k]['group'] = $v['xing'].$v['ming'];
                }else{
                    $list[$k]['group'] = $v['busname'];
                }

                $list[$k]['RegistrantType'] =  $fun->getStatus($v['RegistrantType'],[1=>'个人',2=>'企业']);
                
                if($v['ifreal'] == 1){
                    $list[$k]['auth_remark'] = '不需要实名认证';
                }else{
                    $apiInfo = $redis->hGetAll('Api_Info_'.$v['api_id']);
                    if($v['auth_status'] == 4 && $apiInfo['regid'] == 66){
                        $url1 = '/admin/vipmanage/attestation/alierr/ids/'.$v['id'];
                        $list[$k]['auth_remark'] = '<span style="color:orange;cursor:pointer;text-decoration:underline; " onclick="errai(\''.$url1.'\')" >查询失败原因</span>';
                    }else{
                        $aa = json_decode($v['auth_remark'],true);
                        $list[$k]['auth_remark'] = empty($aa) ? $v['auth_remark'] :  $aa;

                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
     }

    /**
     * 聚名网实名列表 ids 模板id
     */
    public function slist($ids=null){

        if($this->request->isAjax()){

            $outList = Config::get('out_register');
            $this->checkOut($outList);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('outinfo_list')->alias('o')->join('user_renzhengapi r','r.id=o.rz_id')->join('domain_api a','a.id=r.api_id')
                ->where($where)
                ->count();
            $list = Db::name('outinfo_list')->alias('o')->join('user_renzhengapi r','r.id=o.rz_id')->join('domain_api a','a.id=r.api_id')
                ->field('o.id as oid,r.id,o.reg_id,o.out_reg_id,o.info_status,o.auth_status,o.auth_remark,o.info_remark,o.createtime,o.out_code,a.ifreal,o.createtime')
                ->where($where)->order($sort,$order)->limit($offset,$limit)
                ->select();
            $zcs = $this->getCates();
            $fun = Fun::ini();

            foreach($list as &$v){
                $v['o.zcs'] = isset($zcs[$v['reg_id']]) ? $zcs[$v['reg_id']] : '--';
                if(!in_array($v['auth_status'],[0,1,4]) || $v['o.zcs'] == '商务中国' || $v['ifreal'] == 1 ){
                    $v['op'] = '--';
                }else{
                    $url = '/admin/vipmanage/attestation/resetreal/ids/'.$v['id'].'/flag/'.$v['oid'];
                    $v['op'] = '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-success" title="重新实名">重新实名</button>&nbsp;';
                }


                if($v['auth_status'] != 3){

                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['oid'].'&status='.$v['auth_status'].'&flag=out&pstatus=3';

                    if($v['op'] == '--'){
                        $v['op'] = '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-success" title="实名成功">实名成功</button>&nbsp;';
                    }else{
                        $v['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-success" title="实名成功">实名成功</button>&nbsp;';
                    }
                    
                }

                if($v['auth_status'] != 1 && $v['auth_status'] != 4){
                    
                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['oid'].'&status='.$v['auth_status'].'&flag=out&pstatus=4';

                    if($v['op'] == '--'){
                        $v['op'] = '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-danger" title="实名失败">实名失败</button>&nbsp;';
                    }else{
                        $v['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-danger" title="实名失败">实名失败</button>&nbsp;';
                    }
                    
                }

                $v['o.auth_status'] = $fun->getStatus($v['auth_status'],['<font color="#e74c3c">未实名</font>','<font color="#e74c3c">实名提交失败</font>','<font color="#3498db">提交成功</font>','<font color="#18bc9c">认证成功</font>','<font color="#e74c3c">注册商实名失败</font>',9=>'<font color="#e74c3c">实名查询结果时模板不存在</font>']);

                $v['o.info_status'] =  $fun->getStatus($v['info_status'],[1=>'创建失败','创建成功',9=>'申请手动添加']);
//                $v['email_status'] =  $fun->getStatus($v['email_status'],['未认证',2=>'已认证']);
                $v['o.createtime'] = $v['createtime'];
                $aa = json_decode($v['auth_remark'],true);
                $v['o.auth_remark'] = empty($aa) ? $v['auth_remark'] :  $aa;
                $v['out_reg_id'] = empty($outList[$v['out_reg_id']]) ? '--' : $outList[$v['out_reg_id']];

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('id',$ids);
        return $this->view->fetch();
    }

    /**
     * 检测并添加不存在的外部注册商
     */
    private function checkOut($outList){
        $param = json_decode($this->request->param('filter'),true);
        if(empty($param['rz_id'])){
            $this->error('缺少重要id参数');
        }
        $list = Db::name('outinfo_list')->where('rz_id',$param['rz_id'])->column('out_code');
        //获取未添加的外部注册商
        $extra = array_diff($outList,$list);

        if($extra){
            $apiInfo = Db::name('user_renzhengapi z')->join('domain_api a','z.api_id=a.id')
                ->field('z.info_status,z.info_remark,z.createtime,z.userid,a.regid as reg_id')
                ->where('z.id',$param['rz_id'])
                ->find();
            if(empty($apiInfo)){
                $this->error('模板获取失败');
            }
            $inserts = [];
            foreach($extra as $k => $v){

                $apiInfo['out_reg_id'] = $k;
                $apiInfo['rz_id'] = $param['rz_id'];
                $apiInfo['auth_status'] = 1;
                $apiInfo['auth_remark'] = '默认添加外部注册商,需要重新手动提交!';
                $apiInfo['out_code'] = $v;
                $inserts[] = $apiInfo;
            }
            Db::name('outinfo_list')->insertAll($inserts);
        }

    }

    /**
     * 查看
     */
     public function edit($ids = ''){
        if($this->request->isPost()){
            $param = $this->request->post('row/a');
          
            if(empty($param['id'])){
                $this->error('缺少重要参数');
            }
            //修改邮箱
            if(isset($param['info_id'])){
                $email = $this->request->post('email');
                if($email){
                    if(!preg_match('/^(\w-*\.*)+@(\w-?)+(\.\w{2,})+$/',$email)){
                        $this->error('邮箱格式不正确');
                    }
                    $update['Email'] = $email;
                }
                
                $mot = $this->request->post('mot');
                if(!preg_match('/^1[3456789]\d{9}$/',$mot)){
                    $this->error('手机格式不正确');
                }
                $update['Telephone'] = $mot;
                Db::table(PREFIX.'domain_infoTemplate')->where('id',$param['info_id'])->update($update);
            }
            Db::name('user_renzhengapi')->where('id',$param['id'])->update($param);
            $this->success('操作成功');
        }

        if(empty($ids)){
            return $this->error('缺少重要参数');
        }
        $data = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
            ->join('user_renzhengapi r','r.info_id=t.id','right')
            ->join('user_renzheng re','t.renzheng_id=re.id','left')
            ->join('domain_api a','a.id = r.api_id')
            ->join('domain_user u','u.id=re.userid')
            ->field('u.mot,r.email_status,r.id as rid,t.id,re.Country,re.ZhProvince,re.ZhCity,re.address,re.xing,re.ming,re.renzheng,t.tempstatus,t.title,t.Email,re.PostalCode,t.newstime,t.Telephone,re.renzhengno,re.image1,re.buslicence,re.image2,t.RegistrantType,r.auth_status,r.info_status,r.info_id,a.regid')
            ->where(['r.id' => $ids])
            ->find();
        if($data['regid'] == 66){
            $flag = Db::name('user_renzhengapi')->alias('r')->join('domain_api a','a.id = r.api_id')
                        ->where(['a.regid' => 66,'r.info_status' => 2,'r.info_id' => $data['info_id'] ])
                        ->count();
        }else{
            $flag = false;
        }
        $fun = Fun::ini();
        $data['auth_status'] = $fun->getStatus($data['auth_status'],['未实名','实名提交失败','提交成功','认证成功','注册商实名失败',9=>'实名查询结果时模板不存在']);
//        $data['info_status'] =  $fun->getStatus($data['info_status'],[1=>'创建失败','创建成功',9=>'申请手动添加']);
        $data['tempstatus'] = $fun->getStatus($data['tempstatus'],['正常','隐藏']);
        $this->view->assign(['data' => $data,'flag' => $flag]);
        return $this->view->fetch();
    }
    /**
     * 删除信息模板
     */
    public function del($ids = ''){
        if(empty(intval($ids))){
            return ['code' => 1,'msg' => '删除有误'];
        }
        Db::name('user_renzhengapi')->where('id',$ids)->delete();
        return ['code' => 0,'msg' => '删除成功'];
    }
    /**
     * 重新实名认证
     */
    public function resetreal($ids,$flag=null){

        $apiInfo = Db::table(PREFIX.'domain_infoTemplate')->alias('t1')->join('user_renzhengapi t2','t1.id=t2.info_id','right')->join('user_renzheng t3','t1.renzheng_id = t3.id')
                    ->field('t2.api_id,t2.id,t2.system_id,t3.renzheng,t3.renzhengno,t3.buslicence,t3.image1,t3.image2,t3.image3,t3.xing,t3.ming,t3.busname,t2.userid')
                    ->where(['t2.id' => $ids])
                    ->find();
        if(empty($apiInfo)){
            return ['code' => 1,'msg' => 'api信息有误'];
        }
        $imgpath = $apiInfo['image1'];
        if($apiInfo['api_id'] == 15){ //百度api
            if($apiInfo['renzheng'] == 1){
                $imgpath1 = empty($apiInfo['image3']) ? $apiInfo['image2'] : $apiInfo['image3'];
            }else{
                $imgpath = empty($apiInfo['image3']) ? $apiInfo['image1'] : $apiInfo['image3'];
                $imgpath1 = '';
            }
        }else{
            if($apiInfo['renzheng'] == 1){
                $imgpath1 = $apiInfo['image2'];
            }else{
                $imgpath1 = '';
            }
        }
        
        $image_1 = IMGURL.'alireal/'.$imgpath;
        $imgpath1 = IMGURL.'alireal/'.$imgpath1;

        if($flag){
            $finfo = Db::name('outinfo_list')->where('id',$flag)->field('id as out_id,out_reg_id,out_code')->find();
            if(empty($finfo)){
                $this->error('外部注册商id错误');
            }
            $apiInfo = array_merge($apiInfo,$finfo);

        }

        //易名网 裁剪图像
        if($apiInfo['api_id'] == 33){
            $apiInfo['base64'] = Fun::ini()->resizeImage($image_1);
            if(!$apiInfo['base64']){
                $this->error('裁剪后身份证图片小于55kb,请重新上传图片');
            }
            // 获取参数
            if($apiInfo['renzheng'] == 1){
                $apiInfo['base64_qy'] = Fun::ini()->resizeImage($imgpath1);
                if(!$apiInfo['base64']){
                    $this->error('裁剪后营业执照图片小于55kb,请重新上传图片');
                }
            }
        }else{
            $apiInfo['base64'] = Fun::ini()->base64EncodeImage($image_1);
                // 获取参数
            if($apiInfo['renzheng'] == 1){
                $apiInfo['base64_qy'] = Fun::ini()->base64EncodeImage($imgpath1);
            }
        }
        
        // 获取用户uid
        $uid = Db::name('domain_user')->where('id',$apiInfo['userid'])->value('uid');
        $apiInfo['sendemail'] = $uid;
        // 修改api认证表 实名中状态
        if($flag){
            Db::name('outinfo_list')->where(['id' => $flag])->update(['auth_status' => 2]);
        }else{
            Db::name('user_renzhengapi')->where(['id' => $apiInfo['id']])->update(['auth_status' => 2]);
        }

        // 提交任务 使用5号库
        $redis = new Redis(['select' => 5]);
        if($redis->hgetall('real_reset_submit_info_'.$apiInfo['id'])){
            return ['code' => 0,'msg' => '任务已经在队列中'];
        }

        $key = empty($apiInfo['out_reg_id']) ? $apiInfo['id'] : ($apiInfo['id'].'_'.$apiInfo['api_id'].'_'.$apiInfo['out_reg_id']);
        
        $redis->lpush('real_reset_submit',$key);

        $redis->hmset('real_reset_submit_info_'.$key,$apiInfo);

        return ['code' => 0,'msg' => '任务重新提交成功'];
    }
    /**
     * 查询阿里云实名失败原因
     */
    public function alierr($ids){
        if($this->request->isAjax()){

            $info = Db::name('user_renzhengapi')->field('api_id,system_id')->where(['id' => $ids,'auth_status' => 4])->find();
            if(empty($info)){
                return ['code' => 1,'msg' => '模板信息错误'];
            }
            
            $redis = new Redis();
            $apiInfo = $redis->hGetAll('Api_Info_'.$info['api_id']);
            
            if(empty($apiInfo)){
                return ['code' => 1,'msg' => 'api信息获取失败'];
            }

            if($apiInfo['regid'] != 66 ){
                return ['code' => 1,'msg' => '仅支持阿里云的实名失败原因查询'];
            }
            $obj = new AliyunApi($apiInfo['region'],$apiInfo['accessKey'],$apiInfo['secret']);

            $res = $obj->QueryFailReasonForRegistrantProfileRealNameVerification($info['system_id']);
            if(isset($res['Data'])){
                $errs = array_unique(array_column($res['Data'],'FailReason'));
                $errmsg = implode('<br>',$errs);
                return ['code' => 0 ,'msg' => $errmsg];
            }
            return ['code' => 1,'msg' => '接口调用失败!'];


        }

    }

    /**
     * 更改实名状态
     */
    public function updateStatus(){

        if($this->request->isAjax()){
            $param = $this->request->get();

            $id = empty($param['id']) ? 0 : intval($param['id']);

            

            $status = !isset($param['status']) ? null : intval($param['status']);

            if(empty($id) || $status === null){
                return ['code' => 1,'msg' => '缺少重要参数'];
            }


            if(empty($param['pstatus']) || !in_array($param['pstatus'],[3,4])){
                return ['code' => 1,'msg' => '状态取值不在可选范围内'];
            }


            if(isset($param['flag']) && $param['flag'] == 'out'){
                $table = 'outinfo_list';
            }else{
                $table = 'user_renzhengapi';
            }
            $r = $param['pstatus'] == 3 ? 2 : 1;
            $flag = Db::name($table)->where(['id' => $id,'auth_status' => $status])->count();
            if(empty($flag)){
                return ['code' => 1,'msg' => '该模板不存在或者状态已被修改,请刷新页面后重试！'];
            }

            Db::name($table)->where('id',$id)->update(['auth_status' => $param['pstatus'],'info_status' => $r,'info_remark' => '手动修改模板状态','auth_remark' => '手动修改实名状态']);

            return ['code' => 0,'msg' => '操作成功'];


        }

    }


}


