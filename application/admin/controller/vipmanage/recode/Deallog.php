<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use App\Exception\Library\Exception;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名交易记录
 *
 * @icon fa fa-user
 */
class Deallog extends Backend
{
    protected $noNeedRight = ['show'];
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_order');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit,$tits,$hz) = $this->buildparams();

            $def = ' c.status in (0,1) ';
            if($tits){
                $TextAv=str_replace("\r","",$tits);
                $Text=preg_split("/\n/",$TextAv);
                $Text = preg_replace('/\s+/is','',array_filter($Text));
                if(count($Text) > 300){
                    $Text = array_splice($Text,0,300);
                }
                $tits = '"'.implode('","',$Text).'"';
                $def .= ' and (c.tit in("'.implode('","',$Text).'") ';
                foreach($Text as $v){
                    $def .= ' or find_in_set("'.$v.'",c.pack) ';
                }
                $def .= ')';
            }
            if($hz){
                $def .= ' and REPLACE(c.tit,substring_index(c.tit,".",1),"") = "'.$hz.'"  ';
            }


            $total = $this->model->alias('c')->join('domain_user u','c.userid=u.id','left')->join('domain_user s','c.selleruserid=s.id','left')->join('storeconfig p','c.selleruserid=p.userid','left')
                        ->where($where)->where($def)
                        ->count();

            $list = $this->model->alias('c')->join('domain_user u','c.userid=u.id','left')->join('domain_user s','c.selleruserid=s.id','left')->join('storeconfig p','c.selleruserid=p.userid','left')
                        ->field('u.uid as uuid,u.id,s.uid as suid,c.sxf,c.money,c.paytime,c.is_sift,c.tit,c.sj,c.bc,c.status,c.final_money,c.type,c.tmoney,p.flag,c.id as cid,c.selleruserid,c.userid,c.sptype,c.agent_cost')
                        ->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)
                        ->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money) as n,sum(final_money) as s, sum(sxf) as f,sum(tmoney) as t FROM '.PREFIX.'domain_order as c WHERE  '.$def;
            }else{
                $conm = 'SELECT sum(c.money) as n ,sum(c.final_money) as s, sum(sxf) as f,sum(c.tmoney) as t FROM '.PREFIX.'domain_order as c LEFT JOIN '.PREFIX.'domain_user as u ON c.userid=u.id LEFT JOIN '.PREFIX.'domain_user as s ON c.selleruserid=s.id left join '.PREFIX.'storeconfig p on c.selleruserid=p.userid '.$sql.' AND '.$def;
            }

            $res = Db::query($conm);
            $fun = Fun::ini();
            $dtime = strtotime('-14 day');
            foreach($list as $k => $v){

                $arrs = explode('.',$v['tit']);
                $list[$k]['special_condition'] =  str_replace($arrs[0],'',$v['tit']);

                if($v['type'] == 9){
                    $v['tit'] .= '<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showPack('.$v['cid'].')" >查看更多</span>';
                }

                $list[$k]['group'] = $v['tit'];
                $list[$k]['c.status'] = $fun->getStatus($v['status'],['<span style="color: red;">未支付</span>','<span style="color: green;">支付成功</span>','<span style="color: orange;">已退款</span>']);
                $list[$k]['c.type'] = $fun->getStatus($v['type'],['正常订单','满减订单','微信活动订单',9=>'打包域名订单']);
                $list[$k]['u.uid'] = $v['uuid']; 
                $list[$k]['s.uid'] = $v['suid']; 
                $list[$k]['c.sj'] = $v['sj'];
                $list[$k]['zje'] = $res[0]['n'];
                $list[$k]['zje1'] = $res[0]['s'];
                $list[$k]['tmon'] = $res[0]['t'];
                $list[$k]['sxfzje'] = $res[0]['f'];
                $list[$k]['p.flag'] = $fun->getStatus($v['flag'],['普通店铺','<span style="color:red">怀米网店铺</span>','<span style="color:green">消保店铺</span>']);
                $list[$k]['c.sptype'] = $fun->getStatus($v['sptype'],['官网','<span style="color:red">推广员</span>','<span style="color:orange">怀米大使</span>','<span style="color:deeppink;">分销系统</span>']);
                if($v['sptype'] == 3){
                    $list[$k]['final_money'] = $v['final_money'].'<br><span style="color:red;font-size:8px;">卖出'.$v['agent_cost'].'</span>';    
                }

                if($v['sptype'] != 3 && $v['status'] == 1 && $dtime < strtotime($v['paytime']) && $this->auth->id == 1){ //退款按钮
                    $list[$k]['flag'] = true;
                }else{
                    $list[$k]['flag'] = false;
                }

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $requ = $this->request->get();
        $this->view->assign([
            'status'=>empty($requ['c.status']) ? '' : $requ['c.status'],
            'ids'   =>empty($requ['u.uid']) ? '' : $requ['u.uid'],
            'bc'   =>empty($requ['c.bc']) ? '' : $requ['bc'],
            'eid'   =>empty($requ['eid']) ? '' : $requ['eid'], //委托购买id
        ]);
        return $this->view->fetch();
    }

    /**
     * 查看详情
     */
    public function show(){

        if($this->request->isAjax()){
            $id = $this->request->post('id');
            if(empty($id)){
                return ['code' => 1,'msg' => '缺少重要参数'];
            }
            $tits = $this->model->where('id',$id)->value('pack');
            return ['code' => 0,'msg' => 'success','data' => $tits];

        }

    }

    /**
     * 订单退款
     */
    public function refund(){
        if($this->request->isAjax()){

            $oid = $this->request->get('id','','intval');

            if(empty($oid)){
                $this->error('参数有误');
            }

            $info = $this->model->field('bc,userid,tit,type,selleruserid,pack,final_money,sxf')->where(['id' => $oid,'status' => 1])->where('sptype != 3')->find();
            if(empty($info)){
                $this->error('该订单不存在或状态已发生改变');
            }

            $tits = [$info['tit']];
            if($info['type'] == 9){
                $tits = array_merge($tits,array_filter(explode(',',$info['pack'])));
            }

            //判断是否归属买家
            $coTits = Db::name('domain_pro_n')->where(['userid' => $info['userid'],'zt' => 9])->whereIn('tit',$tits)->column('tit');
            $diffTits = array_diff($tits,$coTits);

            if($diffTits){
                $this->error('域名:'.implode(',',$diffTits).'已不归属买家或域名处于非正常状态');
            }

            $money = $info['final_money'] - $info['sxf'];

            //锁定买卖家余额
            if(!Fun::ini()->lockMoney($info['userid']) &&  !Fun::ini()->lockMoney($info['selleruserid'])){
                $this->error('系统繁忙,请稍后操作');
            }

            //获取卖家可用余额
            $smoney = Db::name('domain_user')->field('money1,baomoney1')->where('id',$info['selleruserid'])->find();

            if($smoney['money1'] - $smoney['baomoney1'] < $money){
                Fun::ini()->unlockMoney($info['userid']);
                Fun::ini()->unlockMoney($info['selleruserid']);
                $this->error('卖家余额不足');
            }

            //获取买家金额
            $bmoney = Db::name('domain_user')->where('id',$info['userid'])->value('money1');

            Db::startTrans();
            try{
                //扣除卖家实际得到的金额
                Db::name('domain_user')->where('id',$info['selleruserid'])->setDec('money1',$money);
                //返还买家金额
                Db::name('domain_user')->where('id',$info['userid'])->setInc('money1',$money);
                //更换域名归属
                Db::name('domain_pro_n')->whereIn('tit',$tits)->setField('userid',$info['selleruserid']);
                //插入资金明细
                Db::name('flow_record')->insert([
                    'sj' => date('Y-m-d H:i:s'),
                    'infoid' => $info['bc'],
                    'product' => 8,
                    'subtype' => 22,
                    'uip' => '',
                    'money' => $money,
                    'userid' => $info['userid'],
                    'balance' => ($bmoney + $money),
                ]);
                //插入管理员操作记录
                Db::name('domain_operate_record')->insert([
                    'create_time' => time(),
                    'tit'=> $info['tit'],
                    'operator_id' => $this->auth->id,
                    'type' => 10,
                    'value' => '一口价交易退款'
                ]);

            }catch(\Exception $e){
                Fun::ini()->unlockMoney($info['userid']);
                Fun::ini()->unlockMoney($info['selleruserid']);
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();
            Fun::ini()->unlockMoney($info['userid']);
            Fun::ini()->unlockMoney($info['selleruserid']);
            $this->success('退款成功');

        }
    }


}
