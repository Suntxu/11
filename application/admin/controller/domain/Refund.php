<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 域名退款 -- 只做一个记录
 */
class Refund extends Backend
{

    protected $model = null;

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
        if ($this->request->isPost()) {
            $domain = $this->request->param("domain");
            
            if(empty($domain)){
                $this->error('请输入要查询的域名','/admin/domain/refund');
            }
            $a = Fun::ini()->moreRow($domain);
            if(count($a) > 200){
                $this->error('每次最多提交200个域名');
            }

            $total = $this->model->alias('d')->join('domain_user u','d.userid=u.id')->join('yj_Task_Detail r','d.tit = r.tit and d.userid = r.userid')
                ->where('d.tit','in',$a)->where(' r.TaskStatusCode = 2 and d.zt = 9 ')
                ->count();
            $data = $this->model->alias('d')->join('domain_user u','d.userid=u.id')->join('yj_Task_Detail r','d.tit = r.tit and d.userid = r.userid')
                    ->field('d.id,d.tit,r.money,r.CreateTime,r.api_id,u.uid')
                    ->where('d.tit','in',$a)->where(' r.TaskStatusCode = 2  and d.zt = 9 ')
                    ->select();
            $apis = $this->getApis(-1);
            foreach($data as &$v){
                if(isset($apis[$v['api_id']])){
                    $v['zcs'] = $apis[$v['api_id']]['regname'];
                }else{
                    $v['zcs'] = '--';
                }
            }
            $this->view->assign([
                'domain' =>  array_column($data,'tit'),
                'data' => $data,
                'show' => 'aa',
                'total' => $total,
            ]);
            return $this->view->fetch();
        }
        //批量修改域名列表
        $id = $this->request->get('id',0);
        $domain = $this->model->where('id','in',$id)->column('tit');
        $this->view->assign([
            'domain' => $domain,
            'show' => 'aaaa',
            'total' => 0,
        ]);
        return $this->view->fetch();
    }
    
    /**
     * 进行退款操作
     */
    public function opdomain(){
        if($this->request->isAjax()){
            $domain = $this->request->post('domain');
            $a = Fun::ini()->moreRow($domain);

            if(count($a) > 200){
                return ['code' => 1,'msg' => '每次最多提交200个域名'];
            }
            $domainInfo = $this->model->alias('d')->join('yj_Task_Detail r','d.tit = r.tit and d.userid = r.userid')
                    ->field('d.tit,r.money,r.api_id,r.userid,r.CreateTime')
                    ->where('d.tit','in',$a)->where(' r.TaskStatusCode = 2 and d.zt = 9 ')
                    ->select();

            if(empty($domainInfo)){
                return ['code' => 1,'msg' => '域名状态不正确,请确认!'];
            }
            $domains = array_column($domainInfo,'tit');

            $zcss = $this->getApis(-1);
            $time = time();
            //按照用户名重新拼接数组
            $arew = [];
            foreach ($domainInfo as $key => $v) {
                $arew[$v['userid']][$key] = ['tit' => $v['tit'],'money' => $v['money'],'api_id' => $v['api_id'],'zcs' => $zcss[$v['api_id']]['regid'],'atime' => $v['CreateTime'],'userid' => $v['userid'],'create_time' => $time ];
            }
            $sj = date('Y-m-d H:i:s');
            $uip = $this->request->ip();
            Db::startTrans();
            try{
                
                //删除
                Db::name('domain_cart')->whereIn('tit',$domains)->delete();
                Db::name('domain_follow')->whereIn('tit',$domains)->delete();
                Db::name('domain_order')->where('status != 1')->whereIn('tit',$domains)->delete();
                // 删除解析日志
                Db::name('domain_record')->whereIn('tit',$domains)->delete();
                // 删除一口价
                // Db::name('domain_pro_trade')->whereIn('tit',$domains)->delete();
                // 出库
                Db::name('domain_pro_n')->whereIn('tit',$domains)->delete();
                //操作记录
                $oid = Db::name('domain_operate_record')->insertGetId(['create_time'=>$time,'tit'=>implode(',',$domains),'operator_id'=>$this->auth->id,'type' => 5,'value' => '域名退款']);
                
                foreach($arew as $k => $v){
                    //获取改用户要退款的总额
                    $money = sprintf('%.2f',array_sum(array_column($v,'money')));
                    //将oid 拼进数组
                    array_walk($v, function(&$val,$Kl,$oid){ $val['oid'] = $oid; },$oid);
                   
                    //插入各个用户的退款详情
                    Db::name('domain_refund_log')->insertAll($v);

                    //退款资金
                    Db::name('domain_user')->where('id',$k)->setInc('money1',$money);
                    $umoney = Db::name('domain_user')->where('id',$k)->value('money1');
                    //资金明细
                    Db::name('flow_record')->insert([ 
                        'sj' => $sj,
                        'infoid' => $oid,
                        'product' => 8,
                        'subtype' => 17,
                        'uip' => $uip,
                        'money' => $money,
                        'userid' => $k,
                        'balance' => $umoney,
                    ]);
                }
                Db::commit();
                return ['code' => 0,'msg' => '退款成功'];

            }catch(Exception $e){
                Db::rollback();
                return ['code' => 1,'msg' => $e->getMessage()];
            }
        }

    }
   

}
