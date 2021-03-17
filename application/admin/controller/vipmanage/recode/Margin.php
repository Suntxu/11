<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\common\sendMail;

/**
 * 保证金记录管理
 *
 * @icon fa fa-list
 * @remark 用于统一管理保证金记录
 */
class Margin extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_baomoneyrecord');
    }
   
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('b')
                ->join('domain_user u','b.userid = u.id','left')
                ->where($where)
                ->count();
            $list = $this->model
                ->alias('b')
                ->join('domain_user u','b.userid = u.id','left')
                ->field('b.id,b.tit,b.moneynum,b.sj,b.uip,b.status,b.sremark,b.type,u.uid,b.infoid,b.userid')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            
            foreach ($list as &$item){
                $item['status'] = $fun->getStatus($item['status'],['冻结中','扣除','还原']);
                $item['b.sj'] = $item['sj'];
                switch ($item['type']) {
                    case 1:
                        $show = '/admin/domain/into/shows?id='.$item['infoid'];
                       break;
                    case 2:
                        $show = '/admin/vipmanage/tx/edit/ids/'.$item['infoid'];
                       break;
                    case 3:
                        $show = '/admin/vipmanage/bill/bill/edit/ids/'.$item['infoid'];
                       break;
                    case 5:
                        $show = '/admin/domain/reserve/domainreserve/edit/ids/'.$item['infoid'];
                       break;
                    case 10:
                        $show = '/admin/vipmanage/recode/transfershow/index?type=2&tid='.$item['infoid'];
                       break;
                    case 12:
                        $show = '/admin/vipmanage/recode/transfershow/index?type=4&tid='.$item['infoid'];
                       break;
                    case 13:
                        $show = '/admin/domain/autoentrust?id='.$item['infoid'];
                       break;
                   default:
                       $show = '';
                       break;
               }
               
               if($show){
                    $item['showurl'] = '<a href="'.$show.'" class="dialogit"  title="详情">详情</a>';
               }else{
                    $item['showurl'] = '--';
               }
               $item['type'] = $fun->getStatus($item['type'],['系统扣除','转回原注册商','提现','发票申请','店铺保证金','域名预订','域名竞价','域名竞价额外冻结','预释放','拼团','域名注册冻结','--','域名续费','委托购买']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        $this->assign('id',$this->request->get('id')); //资金明细

        return $this->view->fetch();
    }

    // 还原系统扣除的保证金
    public function policy(int $ids){

        if($this->request->isAjax()){
           
            $info = Db::name('domain_baomoneyrecord')->alias('b')->join('domain_user u','b.userid=u.id ')
                ->where(['b.id' => $ids,'b.status' => 0,'b.type' => 0])
                ->field('b.moneynum,b.userid,b.tit,u.uid,u.mot')
                ->find();
            if(empty($info)){
                $this->error('保证金记录不存在');
            }
            Db::startTrans();
            try{
                if(!Fun::ini()->lockBaoMoney($info['userid'])){
                    throw new \Exception("系统繁忙,请稍后再试");
                }
                Db::name('domain_user')->where('id',$info['userid'])->setDec('baomoney1',$info['moneynum']);
                Db::name('domain_baomoneyrecord')->where('id',$ids)->update(['status' => 2,'otime' => time()]);

            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            Fun::ini()->unlockBaoMoney($info['userid']);
            Db::commit();
            //发送邮件和系统消息
            $e = new sendMail();
            $e->unfreezing($info);
            $this->success('操作成功');
        }

    }

    //扣除保证金
    public function deduct(int $ids){

        if($this->request->isAjax()){

            $info = Db::name('domain_baomoneyrecord')->alias('b')->join('domain_user u','b.userid=u.id ')
                ->where(['b.id' => $ids,'b.status' => 0,'b.type' => 0])
                ->field('b.moneynum,b.userid,b.tit,u.uid,u.mot,u.money1')
                ->find();
            if(empty($info)){
                $this->error('保证金记录不存在');
            }
            Db::startTrans();
            try{

                if(!Fun::ini()->lockBaoMoney($info['userid'])){
                    throw new \Exception("系统繁忙,请稍后再试");
                }

                if(!Fun::ini()->lockMoney($info['userid'])){
                    Fun::ini()->unlockBaoMoney($info['userid']);
                    throw new \Exception("系统繁忙,请稍后再试1");
                }

                Db::query('update '.PREFIX.'domain_user set baomoney1 = baomoney1 - '.$info['moneynum'].',money1 = money1 - '.$info['moneynum'].' where id = '.$info['userid']);
                Db::name('domain_baomoneyrecord')->where('id',$ids)->update(['status' => 1,'otime' => time()]);
                Db::name('flow_record')->insert([
                    'sj'    => date('Y-m-d H:i:s'),
                    'infoid'=> $ids,
                    'product'=> 2,
                    'subtype'=> 5,
                    'uip'   => '',
                    'info' => '冻结资金扣除,'.$info['tit'],
                    'balance' => ($info['money1']-$info['moneynum']),
                    'money' => -$info['moneynum'],
                    'userid'=> $info['userid'],
                ]);
            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            Fun::ini()->unlockBaoMoney($info['userid']);
            Fun::ini()->unlockMoney($info['userid']);
            Db::commit();
            //发送邮件和系统消息
            $e = new sendMail();
            $e->deductfreezing($info);
            $this->success('操作成功');
        }

    }
}