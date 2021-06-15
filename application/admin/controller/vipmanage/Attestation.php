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
                    ->field('r.system_id,t.id as tid,r.id,u.uid,a.tit,c.name,r.auth_status,r.info_status,r.auth_remark,r.info_remark,r.createtime,a.ifreal,t.title,info_id,t.RegistrantType,re.xing,re.ming,re.busname,r.api_id,c.id as cid')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $redis = new Redis();
            $fun = Fun::ini();

            foreach($list as $k=>$v){
                $list[$k]['op'] = '';
                if( $v['info_status'] != 2 || !in_array($v['auth_status'],[0,1,4]) || $v['cid'] == 74 || $v['cid'] == 86 || $v['cid'] == 111 || $v['cid'] == 113 || $v['ifreal'] == 1){
//                    $list[$k]['op'] .= '--';
                }else if($v['cid'] != 107){
                    $url = '/admin/vipmanage/attestation/resetreal/ids/'.$v['id'];
                    $list[$k]['op'] .= '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-success" title="重新实名">重新实名</button>&nbsp;';
                }

                if(!in_array($v['auth_status'],[2,3])){
//                    $url = '/admin/vipmanage/attestation/del/ids/'.$v['id'];
//                    $list[$k]['op'] .= '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-del btn-warning" title="删除">删除</button>&nbsp;';
                    $url = '/admin/vipmanage/attestation/oneaddinfo?id=' . $v['id'];
                    $list[$k]['op'] .= '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-del btn-warning" title="重新添加模板">重新添加模板</button>&nbsp;';

                }

                if($v['cid'] == 107){
                    $list[$k]['op'] .= '<a href="/admin/vipmanage/attestation/slist/ids/'.$v['id'].'" class="btn btn-xs btn-warning dialogit" title="跳转">查看</a>&nbsp;';
                }
                if($v['auth_status'] != 3){
                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['id'].'&status='.$v['auth_status'].'&pstatus=3';
                    $list[$k]['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-success" title="实名成功">实名成功</button>&nbsp;';
                }

                if($v['auth_status'] != 1 && $v['auth_status'] != 4){
                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['id'].'&status='.$v['auth_status'].'&pstatus=4';
                    $list[$k]['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-danger" title="实名失败">实名失败</button>&nbsp;';
                }

                $list[$k]['auth_status'] = $fun->getStatus($v['auth_status'],['<font color="#e74c3c">未实名</font>','<font color="#e74c3c">实名提交失败</font>','<font color="#3498db">提交成功</font>','<font color="#18bc9c">认证成功</font>','<font color="#e74c3c">注册商实名失败</font>',9=>'<font color="#e74c3c">实名查询结果时模板不存在</font>']);
              
                $list[$k]['info_status'] =  $fun->getStatus($v['info_status'],[1=>'创建失败','创建成功',9=>'申请手动添加']);
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

                if(mb_strlen($v['info_remark']) > 15){
                    $list[$k]['info_remark'] = $fun->returntitdian($v['info_remark'],15).'<span class="show_value" style="cursor:pointer;color:#3c8dbc;" onclick="showRemark(\''.$v['info_remark'].'\')" >查看</span>';
                }

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
                        if(mb_strlen($v['auth_remark']) > 15){
                            $list[$k]['auth_remark'] = $fun->returntitdian($v['auth_remark'],15).'<span class="show_value" style="cursor:pointer;color:#3c8dbc;" onclick="showRemark(\''.$v['auth_remark'].'\')" >查看</span>';
                        }
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


            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = Db::name('outinfo_list')->alias('o')->join('user_renzhengapi r','r.id=o.rz_id')->join('domain_api a','a.id=r.api_id')
                ->field('o.id as oid,r.id,o.reg_id,o.out_reg_id,o.info_status,o.auth_status,o.auth_remark,o.info_remark,o.createtime,o.out_code,a.ifreal')
                ->where($where)->order($sort,$order)
                ->select();

            $outList = Config::get('out_register');

            $info = $this->getOutData($outList,array_column($list,'out_code'));

            $data = array_merge($list,$info);
            $total = count($data);
            //分页后的数据
            $pageData = array_slice($data,$offset,$limit);

            $zcs = $this->getCates();
            $fun = Fun::ini();
            foreach($pageData as &$v){
                $v['o.zcs'] = isset($zcs[$v['reg_id']]) ? $zcs[$v['reg_id']] : '--';
                if(!in_array($v['auth_status'],[0,1,4]) || $v['o.zcs'] == '商务中国' || $v['ifreal'] == 1 ){
                    $v['op'] = '--';
                }else{
                    $url = '/admin/vipmanage/attestation/resetreal/ids/'.$v['id'].'/flag/'.$v['oid'];
                    $v['op'] = '<button type="button" onclick="real(\''.$url.'\')" class="btn btn-xs btn-success" title="重新实名">重新实名</button>&nbsp;';
                }

                if($v['auth_status'] != 3){

                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['oid'].'&status='.$v['auth_status'].'&rz_id='.$v['id'].'&pstatus=3';

                    if($v['op'] == '--'){
                        $v['op'] = '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-success" title="实名成功">实名成功</button>&nbsp;';
                    }else{
                        $v['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-success" title="实名成功">实名成功</button>&nbsp;';
                    }
                    
                }

                if($v['auth_status'] != 1 && $v['auth_status'] != 4){
                    
                    $yurl = '/admin/vipmanage/attestation/updateStatus?id='.$v['oid'].'&status='.$v['auth_status'].'&rz_id='.$v['id'].'&pstatus=4';

                    if($v['op'] == '--'){
                        $v['op'] = '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-danger" title="实名失败">实名失败</button>&nbsp;';
                    }else{
                        $v['op'] .= '<button type="button" onclick="real(\''.$yurl.'\')" class="btn btn-xs btn-del btn-danger" title="实名失败">实名失败</button>&nbsp;';
                    }
                    
                }

                $v['o.auth_status'] = $fun->getStatus($v['auth_status'],['<font color="#e74c3c">未实名</font>','<font color="#e74c3c">实名提交失败</font>','<font color="#3498db">提交成功</font>','<font color="#18bc9c">认证成功</font>','<font color="#e74c3c">注册商实名失败</font>',9=>'<font color="#e74c3c">实名查询结果时模板不存在</font>']);

                $v['o.info_status'] =  $fun->getStatus($v['info_status'],[1=>'创建失败','创建成功',9=>'申请手动添加']);
//                $v['email_status'] =  $fun->getStatus($v['email_status'],['未认证',2=>'已认证']);
                $v['o.createtime'] = isset($v['createtime']) ? $v['createtime'] : '';
                $aa = json_decode($v['auth_remark'],true);
                $v['o.auth_remark'] = empty($aa) ? $v['auth_remark'] :  $aa;
                $v['out_reg_id'] = empty($outList[$v['out_reg_id']]) ? '--' : $outList[$v['out_reg_id']];

            }

            $result = array("total" => $total, "rows" => $pageData);
            return json($result);
        }
        $this->view->assign('id',$ids);
        return $this->view->fetch();
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
            if($flag < 0){ // 负数 插入
                $finfo['out_reg_id'] = abs($flag);
                list($oid,$outName) = $this->insertOut($ids,$finfo['out_reg_id']);
                $finfo['out_id'] = $oid;
                $finfo['out_code'] = $outName;
            }else{
                $finfo = Db::name('outinfo_list')->where('id',$flag)->field('id as out_id,out_reg_id,out_code')->find();
                if(empty($finfo)){
                    $this->error('外部注册商id错误');
                }
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
            $param = $this->request->param();
            $id = empty($param['id']) ? 0 : intval($param['id']);

            

            $status = !isset($param['status']) ? null : intval($param['status']);

            if(empty($id) || $status === null){
                return ['code' => 1,'msg' => '缺少重要参数'];
            }


            if(empty($param['pstatus']) || !in_array($param['pstatus'],[3,4])){
                return ['code' => 1,'msg' => '状态取值不在可选范围内'];
            }


            if(isset($param['rz_id'])){
                $table = 'outinfo_list';
                if($id < 0){ //插入
                    list($id,$outName) = $this->insertOut(intval($param['rz_id']),abs($id));
                }
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

    /**
     * 插入外部注册商模板
     * @rzid  int  模板id
     * @rouRegId int 外部注册商id
     */
    private function insertOut($rzId,$outRegId){

        $outList = Config::get('out_register');

        isset($outList[$outRegId]) || $this->error('外部注册商id错误');

        $apiInfo = Db::name('user_renzhengapi z')->join('domain_api a','z.api_id=a.id')
            ->field('z.info_status,z.info_remark,z.createtime,z.userid,a.regid as reg_id')
            ->where('z.id',$rzId)
            ->find();
        if(empty($apiInfo)){
            $this->error('模板获取失败');
        }
        $apiInfo['out_reg_id'] = $outRegId;
        $apiInfo['rz_id'] = $rzId;
        $apiInfo['auth_status'] = 1;
        $apiInfo['auth_remark'] = '';
        $apiInfo['out_code'] = $outList[$outRegId];
        $id = Db::name('outinfo_list')->insertGetId($apiInfo);
        return [$id,$outList[$outRegId]];
    }

    /**
     * 获取不存在的外部注册商
     * @outList array 全部外部注册商
     * @outCodes array 已存在的数据code
     */
    private function getOutData($outList,$outCodes){
        $param = json_decode($this->request->param('filter'),true);
        if(empty($param['rz_id'])){
            $this->error('缺少重要id参数');
        }
        //获取未添加的外部注册商
        $extra = array_diff($outList,$outCodes);
        $data = [];
        if($extra ){
            if(isset($param['o.auth_status']) && $param['o.auth_status'] != 1){
                return $data;
            }

            $apiInfo = Db::name('user_renzhengapi z')->join('domain_api a','z.api_id=a.id')
                ->field('z.info_status,z.info_remark,z.userid,a.regid as reg_id,a.ifreal')
                ->where('z.id',$param['rz_id'])
                ->find();
            if(empty($apiInfo)){
                $this->error('模板获取失败');
            }
            foreach($extra as $k => $v){
                //判断条件 注册商id
                if(isset($param['out_reg_id']) && $param['out_reg_id'] != $k){
                    continue;
                }
                if(isset($param['out_code']) && $param['out_code'] != $v){
                    continue;
                }
                if((isset($param['o.info_status']) && $param['o.info_status'] != $apiInfo['info_status'])){
                    continue;
                }

                $data[$k] = $apiInfo;
                $data[$k]['oid'] = -$k;
                $data[$k]['out_reg_id'] = $k;
                $data[$k]['auth_status'] = 1;
                $data[$k]['out_code'] = $v;
                $data[$k]['auth_remark'] = '默认添加外部注册商,需要重新手动提交!';
                $data[$k]['id'] = $param['rz_id'];
            }

        }
        return $data;
    }

    /*
     * 添加信息模板
     * */
    public function oneaddinfo(){
        if($this->request->isAjax()){
            $id = $this->request->param('id');
            $data = Db::name('user_renzhengapi')
                ->where('id',$id)
                ->field('id,info_status,email,api_id,info_id,userid')
                ->find();
            if(empty($data)){
                return ['code' => 1,'msg' => '参数错误，请刷新页面'];
            }

            $redis = new Redis();
            $r_info = $redis->hMget('infotemplate_data_'.$data['id'].'_'.$data['api_id'],['info_id']);

            if($r_info['info_id'] > 0){
                return ['code' => 0,'msg' => '添加信息模板任务提交成功'];
            }

            $info = Db::table(PREFIX.'domain_infoTemplate')
                ->alias('t1')
                ->join('user_renzheng t3','t1.renzheng_id = t3.id')
                ->where(['t1.userid' => $data['userid'],'t1.id' => $data['info_id']])
                ->field('t1.id as info_id,t1.Telephone,t1.Email,t3.*,t1.RegistrantProfileId')
                ->find();
            if(empty($info)){
                return ['code' => 1,'msg' => '数据获取失败'];
            }
            $api_info = $redis->hGetAll('Api_Info_'.$data['api_id']);

            if($api_info['regid'] == 88 || $api_info['regid'] == 110){
                $info['Email'] = !$data['email'] ? $info['Email'] : $data['email'];
                $emailVerify = Db::name('emailverify_record')->where(['email' => $info['Email'],'status' => 1])->field('verifytime,ip')->find();
                if($emailVerify){
                    $info['verifyIp'] = $emailVerify['ip'];
                    $info['verifyTime'] = $emailVerify['verifytime'];
                }
                else
                    return ['code' => 1,'msg' => '模板邮箱未验证，请先验证邮箱'];
            }
            $base64 = $this->imagexs($api_info['regid'],$info['image1']);
            if (!$base64){
                return ['code' => 1,'msg' => '身份证图片裁剪失败'];
            }
            $info['base64'] = $base64;
            if($info['renzheng'] == 1){
                $base64_qy = $this->imagexs($api_info['regid'],$info['image2']);
                if (!$base64_qy){
                    return ['code' => 1,'msg' => '营业执照图片裁剪失败'];
                }
                $info['base64_qy'] = $base64_qy;
            }
            $uid = Db::name('domain_user')->where('id',$data['userid'])->value('uid');
            $info['api_id'] = $data['api_id'];
            $info['sendemail'] = $uid;

            $redis->hMset('infotemplate_data_'.$data['id'].'_'.$data['api_id'],$info);
            $redis->RPush('infotemplate_id',$data['id'].'_'.$data['api_id']);
            if($api_info['regid'] == 110){
                /* 添加国域模板成功后的邮箱验证 */
                $redis->rpush('emailverify_record_'.$api_info['regid'],$info['Email'].'/'.$data['api_id']);
            }
            Db::name('user_renzhengapi')->where('id',$id)->delete();
            return ['code' => 0,'msg' => '添加信息模板成功'];

        }
    }

    public function imagexs($regid,$image){
        $fun = Fun::ini();
        if($regid == 88){
            $filename = $fun->resizeImage(IMGURL.'alireal'.'/'.$image,1950,1950);
            return  $filename;
        }
        return $fun->base64EncodeImage(IMGURL.'alireal' . '/'.$image,1);
    }
}


