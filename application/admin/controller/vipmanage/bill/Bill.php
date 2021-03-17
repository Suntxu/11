<?php

namespace app\admin\controller\vipmanage\bill;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Bill extends Backend
{

    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_bill_recode');
    }
    /**
     *
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('c')->join('domain_bill b','c.bid=b.id')->join('domain_user u','b.userid=u.id')
                                ->where($where)->count();
            $list = $this->model->alias('c')->join('domain_bill b','c.bid=b.id')->join('domain_user u','b.userid=u.id')
                                ->field('b.bname,b.status,b.utype,b.btype,u.uid,c.bh,c.regtime,c.autime,c.money,c.statu,c.remark,c.id,b.khh,b.khname,b.email')
                                ->where($where)->order($sort, $order)->limit($offset, $limit)->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money) as n FROM '.PREFIX.'domain_bill_recode as c left join '.PREFIX.'domain_bill as b on c.bid=b.id left join '.PREFIX.'domain_user as u on b.userid = u.id';
            }else{
                $conm = 'SELECT sum(money) as n FROM '.PREFIX.'domain_bill_recode as c left join '.PREFIX.'domain_bill as b on c.bid=b.id left join '.PREFIX.'domain_user as u on b.userid = u.id '.$sql;
            }
            $res = Db::query($conm);
            $arr = [];
            foreach($list as $k => $v){
                $arr[$k]['b.bname'] = $v['bname'];
                $arr[$k]['b.utype'] = Fun::ini()->getStatus($v['utype'],['----','个人','企业']);
                $arr[$k]['b.btype'] = Fun::ini()->getStatus($v['btype'],['----','增值税普通发票','增值税专用发票']);
                $arr[$k]['zje'] = $res[0]['n'];
                $arr[$k]['c.remark'] = $v['remark'];
                $arr[$k]['u.uid'] = $v['uid'];
                $arr[$k]['c.bh'] = $v['bh'];
                $arr[$k]['c.regtime'] = $v['regtime'];
                $arr[$k]['c.autime'] = $v['autime'];
                $arr[$k]['c.money'] = $v['money'];
                $arr[$k]['id'] = $v['id'];
                $arr[$k]['c.statu'] = Fun::ini()->getStatus($v['statu'],['<span style="color:red">待审核</span>','审核通过','审核失败']);
                $arr[$k]['b.status'] = Fun::ini()->getStatus($v['status'],['<span style="color:red">待处理</span>','处理完成','处理失败']);
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign([
            'id'    => $this->request->get('ids'),
        ]);
        return $this->view->fetch();
    }
     /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                $binfo = Db::name('domain_bill') -> where(['id'=>$params['bid']])->where('status != 1') -> value('id');

                Db::startTrans();

                if($binfo){
                    //修改模板状态
                    Db::name('domain_bill') -> where(['id'=>$params['bid']]) -> update(['status'=>$params['statu']]);
                }

                $rinfo = $this->model->where(['statu' => 0,'id' => $params['id']])->value('id');
                if(empty($rinfo)){
                    $this->success('发票已审核');
                }

                $sj = time();
                // 获取用户保证金信息
                $userInfo = Db::name('domain_baomoneyrecord')->field('uip,sj,moneynum')->where(['infoid'=>$params['id'],'type'=>3])->find();

                if(!empty($userInfo)){
                    //如果审核失败 退回运费
                    if($params['statu'] == 2){
                        Db::name('domain_baomoneyrecord')->where(['infoid' => $params['id'],'type'=>3])->update(['otime' => $sj,'status' => 2,'sremark' => '发票审核失败，已还原']);

                        Fun::ini()->lockBaoMoney($params['userid']) || $this->error('系统繁忙,请稍后操作!');
                        Db::name('domain_user')->where('id',$params['userid'])->setDec('baomoney1',$userInfo['moneynum']);
                        // Db::execute('update '.PREFIX.'domain_user set baomoney1 = baomoney1 - '.$userInfo['moneynum'].',money1 = money1 + '.$userInfo['moneynum'].' where id = '.$params['userid']);
                        Fun::ini()->unlockBaoMoney($params['userid']);
                    }else{
                        $params['error'] = '';

                        Db::name('domain_baomoneyrecord')->where(['infoid' => $params['id'],'type'=>3])->update(['otime' => $sj,'status' => 1,'sremark' => '发票审核成功，已扣除']);

                        Fun::ini()->lockFreezing($params['userid']) || $this->error('系统繁忙,请稍后操作!');

                        Db::execute('update '.PREFIX.'domain_user set baomoney1 = baomoney1 -'.$userInfo['moneynum'].',money1 = money1 -'.$userInfo['moneynum'].' where id = '.$params['userid']);

                        Fun::ini()->unlockFreezing($params['userid']);

                        $umoney = Db::name('domain_user')->where('id',$params['userid'])->value('money1');
                        Db::name('flow_record')->insert([
                                'sj'    => date('Y-m-d H:i:s'),
                                'infoid'=> $params['id'],
                                'product'=> 1,
                                'subtype'=> 6,
                                'uip'   => $userInfo['uip'],
                                'balance' => $umoney,
                                'money' => -$userInfo['moneynum'],
                                'userid'=> $params['userid'],
                            ]);
                    }
                }
                $params['autime'] = $sj;
                if($params['statu'] == 1){
                    $params['error'] = 0;
                }
                $this->model->update($params);
                Db::commit();
                $this->success('操作成功');
            }
            $this->error('无效参数');
        }
        $data = $this->model->alias('c')->join('domain_bill b','c.bid=b.id')->join('domain_user u','b.userid=u.id')
                            ->field('b.bname,b.status,b.userid,b.utype,b.btype,b.bnote,b.address,b.zip,b.sjr,b.tel,b.imsi,u.uid,c.bh,c.regtime,c.autime,c.money,c.statu,c.remark,c.id,c.bid,c.error,b.khh,b.khname,b.email')
                            ->where(['c.id'=>$ids])->find();
        $this->view->assign("data", $data);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $this->model->destroy($ids);
            model('AuthGroupAccess')->where('uid', 'in', $ids)->delete();
            $this->success();

        }
        $this->error();
    }

}
