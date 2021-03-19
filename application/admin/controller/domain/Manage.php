<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use think\Config;
use app\admin\common\sendMail;
/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Manage extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['updateStatus','getDomainHz','getDomainType','queryDomainStatys','getOutZcsList'];
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_pro_n');
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

            $to = date('Y-m-d H:i');
            $date = date('Y-m-d H:i',strtotime('-25 day'));
            $def = '1 = 1';
            if($group){
                if($group == 1){ // 未过期
                    $def .= ' and p.dqsj >= "'.$to.'"';
                }elseif($group == 2){ //过期
                    $def .= ' and p.dqsj between "'.$date.'" and "'.$to.'"';
                }else{ //赎回
                    $def .= ' and p.dqsj < "'.$date.'"';
                }
            }

            $filter = json_decode($this->request->param('filter'),true);
            
            if(empty($filter)){
                $total = $this->model->count();
            }else{
                //解析失败 只获取坏迷网店铺用户 2020/05/08
                if(isset($filter['p.parse_status']) && $filter['p.parse_status'] == 2 ){
                    $suserids = Db::name('storeconfig')->where(['flag' => 1,'shopzt' => 1])->column('userid');
                    if($suserids){
                        $def .= ' and p.userid in ('.implode(',',$suserids).') ';                        
                    }
                }
                $total =  $this->model->alias('p')->join('domain_user u','p.userid=u.id','left')
                    ->where($where)->where($def)
                    ->count();
            }
            $list = $this->model->alias('p')->join('domain_user u','p.userid=u.id','left')
                    ->field('p.hz,p.tit,p.zcsj,p.dqsj,p.inserttime,p.status,p.zt,u.uid,p.api_id,p.zcs,p.id,p.special,p.parse_status,p.dtype,p.infoZR,p.out_zcs')
                    ->where($where)->where($def)->order($sort, $order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();
            $dtype = $fun->getDomainType();
            $apis = $this->getApis(-1);
            $cates = $this->getCates();
            $outList = Config::get('out_register');
            foreach($list as $k=>$v){

                if(empty($v['dtype']) || $v['dtype'] == 'none'){
                    $list[$k]['p.dtype'] = '--';
                }else{
                    $twot = substr($v['dtype'],0,2);
                    if(empty($dtype[$v['dtype'][0]])){
                        $list[$k]['p.dtype'] = empty($dtype[$twot][1][$v['dtype']]) ? '--' : $dtype[$twot][1][$v['dtype']];
                    }else{
                        $list[$k]['p.dtype'] = $dtype[$v['dtype'][0]];
                    }
                }
                $list[$k]['p.zt'] = $fun->getStatus($v['zt'],['--','<span style="color:blue">发布一口价</span>','<span style="color:blue;">打包一口价</span>',4=>'<span style="color:blue;">push域名中</span>',5=>'<span style="color:gray;">转回原注册商</span>',6=>'<span style="color:pink;">域名回收</span>',9=>'<span style="color:green;">正常状态</span>']);
                $list[$k]['p.status'] = $fun->getStatus($v['status'],['<span style="color:green">正常</span>',1=>'<span style="color:red">域名被hold</span>',4=>'<span style="color:red">冻结中</span>']);
                $list[$k]['p.special'] = $fun->getStatus($v['special'],['<span style="color:green">普通</span>','<span style="color:pink">转入</span>','<span style="color:orange">预定</span>','<span style="color:olive">预释放</span>']);
                $list[$k]['p.dqsj'] = substr($v['dqsj'],0,10);
                $list[$k]['p.zcsj'] = substr($v['zcsj'],0,10);
                $list[$k]['api_id'] = empty($apis[$v['api_id']]['tit']) ? '--' : $apis[$v['api_id']]['tit'];
                $list[$k]['zcs'] = $cates[$v['zcs']];
                $list[$k]['p.tit'] = strtolower($v['tit']);
                $list[$k]['p.parse_status'] = $fun->getStatus($v['parse_status'],['<span style="color:gray">入库中</span>','<span style="color:green">添加成功</span>','<span style="color:red">添加失败</span>']);
                $list[$k]['p.infoZR'] = $fun->getStatus($v['infoZR'],['<span style="color:gray">未过户</span>','<span style="color:green">过户成功</span>','<span style="color:red">过户失败</span>','<span style="color:orange;">过户中</span>','<span style="color:red">过户成功,实名失败</span>']);
                $list[$k]['p.hz'] = ltrim($v['hz'],'.');
                if($v['dqsj'] >= $to){
                    $list[$k]['group'] = '<span style="color:green">未过期</span>'; 
                }elseif($v['dqsj'] < $to && $v['dqsj'] > $date){
                    //过期多少天
                    $expire = floor((strtotime($to) - strtotime($list[$k]['p.dqsj']))/86400);
                    $list[$k]['group'] = '<span style="color:orange">已过期 '.$expire.' 天</span>'; 
                }else{
                    $expire = floor((strtotime($to) - strtotime($list[$k]['p.dqsj']))/86400);
                    $list[$k]['group'] = '<span style="color:red">已到期 '.$expire.' 天</span>'; 
                }
                $list[$k]['pstatu'] = '<span id="bbc_'.$v['tit'].'" class="domain_status"><img width="12" src="/assets/libs/layer/dist/theme/default/loading-1.gif"></span>';
                $list[$k]['out_zcs'] = empty($outList[$v['out_zcs']]) ? '--' : $outList[$v['out_zcs']];
            }
            //根据条件统计总金额
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 域名详情 
     */
    public function show(){
        if($this->request->isPost()){
            $rids = $this->request->post('rid/a');
            $RecordIds = $this->request->post('RecordId/a');
            if(count($rids) != count($RecordIds)){
                $this->error('参数不一致');
            }
            $oldRecordId = Db::name('domain_record')->whereIn('id',$rids)->field('id,RecordId')->select();
            $oldVlue = array_combine(array_column($oldRecordId,'id'),array_column($oldRecordId,'RecordId'));
            $newsVlue = array_combine($rids,$RecordIds);
            //计算差集 
            $updateArr = array_diff_assoc($newsVlue,$oldVlue);
            if($updateArr){
                foreach ($updateArr as $k => $v) {
                    Db::name('domain_record')->where('id',$k)->setField('RecordId',$v);
                }
            }
            $this->success('提交成功');
        }
        $ids = $this->request->get('ids');
        if($ids){
            
            $data = Db::name('domain_pro_n')
                    ->field('tit,zcsj,dqsj,inserttime,status,zt,pushid,len,api_id,zcs')
                    ->where(['id' => $ids])
                    ->find();
            $cates = $this->getCates();
            $data['zcs'] = $cates[$data['zcs']];
            $apiinfo= $this->getApis(-1);
            $data['api'] = $apiinfo[$data['api_id']]['tit'];
            $data['zt'] =  Fun::ini()->getStatus($data['zt'],['--','<span style="color:blue">发布一口价</span>','<span style="color:blue;">打包一口价</span>',4=>'<span style="color:blue;">push域名中</span>',5=>'<span style="color:gray;">转回原注册商</span>',9=>'<span style="color:green;">正常状态</span>']);
            $data['status'] = Fun::ini()->getStatus($data['status'],['<span style="color:green">正常</span>',1=>'<span style="color:red">域名被hold</span>',4=>'<span style="color:red">冻结中</span>']);
            $data['inserttime'] = date('Y-m-d H:i:s',$data['inserttime']);
             // 获取DNS 解析记录 域名模板
            $info = Fun::ini()->getWhois($data['tit']);
            $data['whois']['dns'] = empty($info['DNS']) ? '已隐藏' : $info['DNS'];
            $data['whois']['registerName'] = empty($info['联系人']) ? '已隐藏' : $info['联系人'];
            $data['whois']['registerEmail'] = empty($info['联系邮箱']) ? '已隐藏' : $info['联系邮箱'];
            // 获取 当前的解析
            $data['parse'] = Db::name('domain_record')->field('RecordId,id,RR,Type,Value,Line,Status,TTL')->where(['tit'=>$data['tit']])->select();
            $this->view->assign('data',$data);
            return $this->view->fetch();
        }else{
            $this->error('无效参数');
        }
    }
    // 动态加载域名后缀列
    public function getDomainHz(){
        $data = Db::name('domain_houzhui')->column('name1');
        $arr = [];
        foreach($data as $v){
            $arr[$v] = $v;
        }
        return $arr;
    }
   //动态加载域名类型
   public function getDomainType(){
        
        $dtype = Fun::ini()->getDomainType();
        $data = [];
        $i = 0;
        foreach($dtype as $k => $v){
            $data[$i]['id'] = $k;
            $data[$i]['name'] = $v[0];
            if(isset($v[1])){
                foreach($v[1] as $kk => $vv){
                    ++$i;
                    $data[$i]['id'] = $kk;
                    $data[$i]['name'] = '&nbsp;&nbsp;&nbsp;--'.$vv;
                    ++$i;
                }
            }
        }
        return $data;

    }
    
    // 冻结解冻域名
    public function updateStatus(){
        if($this->request->isAjax()){  

            $param = $this->request->post();

            $ids = isset($param['id']) ? $param['id'] : '';

            $status = isset($param['status']) ? intval($param['status']) : '';

            $dremark = isset($param['remark']) ? trim($param['remark']) : '';

            if($ids && $status){
                $dinfo = $this->model->field('userid,tit,zt')->whereIn('id',$ids)->select();
                if(empty($dinfo)){
                    return json(['code' => 1,'msg' => '请选择域名!']);
                }
                $domain = array_column($dinfo,'tit');
                Db::startTrans();
                // 解除冻结
                if($status == 1){
                    // 修改域名状态
                    $this->model->whereIn('tit',$domain)->update(['status' => 0]);
                    Db::name('domain_pro_trade')->whereIn('tit',$domain)->update(['lock' => 0]);
                    $this->sendFrezeeDomainsNotice($dinfo,2,$dremark);
                }elseif($status == 2){

                    $flag = array_unique(array_column($dinfo,'zt'));
                    // $flag = $this->model->whereIn('tit',$domain)->where(['zt' => 6])->count();
                    if(in_array(6, $flag)){
                        $this->error('域名包含正在回收中的域名');
                    }
                    // 修改域名状态
                    $this->model->whereIn('tit',$domain)->update(['status' => 4]);
                    Db::name('domain_pro_trade')->whereIn('tit',$domain)->update(['lock' => 4]);
                    $this->sendFrezeeDomainsNotice($dinfo,1,$dremark);

                }elseif($status == 3){
                    $this->model->whereIn('tit',$domain)->update(['status' => 1]);
                    Db::name('domain_pro_trade')->whereIn('tit',$domain)->update(['lock' => 1]);
                }elseif($status == 4){
                    //修改云解析状态-成功
                    $this->model->whereIn('tit',$domain)->update(['parse_status' => 1]);
                }
                if($status != 4){
                    $remark = Fun::ini()->getStatus($status,[1 => '恢复正常',2 => '冻结',3 => 'hold']);
                    $dremark = empty($dremark) ? '' : '原因:'.$dremark;
                    // 写入记录
                    Db::name('domain_operate_record')->insert(['create_time'=>time(),'tit'=>implode(',',$domain),'operator_id'=>$this->auth->id,'type'=>1,'value'=>$remark.$dremark]);
                }
                Db::commit();
                return json(['code' => 0,'msg' => '操作完成']);
            }else{
                return json(['code' => 1,'msg' => '缺少重要参数']);
            }
        }
        return ['code'=>1,'msg'=>'访问出错'];
       
    }
    /**
     * 查询域名状态
     */
    public function queryDomainStatys(){

        $tit = $this->request->post('tit');

        $res = Fun::ini()->queryDomainStatus($tit);

        if($res['code'] == -1){

            $msg = '<span style="color:red;">域名被hold</span>';

        }else{

            $msg = '<span style="color:green;">正常</span>';

        }
        return ['code' => 0,'msg' => $msg];

    }

    //获取外部注册商
    public function getOutZcsList(){
        $outList = Config::get('out_register');
        return $outList;

    }

    
    /**
     * 冻结域名发送通知
     */
    private function sendFrezeeDomainsNotice($dinfo,$status,$cause=''){
        //查询用户名并发邮件通知
        $uids = Db::name('domain_user')->field('id,uid')->whereIn('id',array_unique(array_column($dinfo,'userid')))->select();
        //拼接数组
        $uinfos = [];
        foreach($uids as $k => $v){
            $uinfos[$k] = $v;
            foreach($dinfo as $vv){
                if($vv['userid'] == $v['id']){
                    $uinfos[$k]['tit'][] = $vv['tit'];
                }
            }   
        }
     
        $send = new sendMail();
        foreach($uinfos as $v){
            $send->domainFrezee($v['id'],$v['uid'],$status,$v['tit'],$cause);
        }

    }



}
