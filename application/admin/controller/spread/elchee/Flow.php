<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 佣金流水记录
 *
 * @icon fa fa-user
 */
class Flow extends Backend
{
    protected $relationSearch = false;
    /**
     * User模型对象
     */
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
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('spreader_flow')->alias('f')->join('domain_user u','f.userid=u.id','left')->join('domain_user u1','u1.id=f.buyeruserid','left')
                    ->where($where)
                    ->count();
            $list = Db::name('spreader_flow')->alias('f')->join('domain_user u','f.userid=u.id','left')->join('domain_user u1','u1.id=f.buyeruserid','left')
                    ->field('u1.uid as uuid,f.type,f.infoid,u.uid,f.paymoney,f.status,f.time,f.updatetime,f.yjtype,f.apptime,f.yj')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(paymoney) as n, sum(yj) as yj FROM '.PREFIX.'spreader_flow';
            }else{
                $conm = 'SELECT sum(f.paymoney) as n,sum(f.yj) as yj FROM '.PREFIX.'spreader_flow f left join  '.PREFIX.'domain_user u on u.id=f.userid left join '.PREFIX.'domain_user u1 on f.buyeruserid=u1.id '.$sql;
            }
            $res = Db::query($conm);
            $zje = sprintf('%.2f',$res[0]['n']);
            $yj = sprintf('%.2f',$res[0]['yj']);
            //根据条件统计总金额
            $fun = Fun::ini();
            foreach($list as $k => $v){
                if($v['type'] == 1){
                    if($v['yjtype'] == 0){
                        $list[$k]['no'] = "<a href='/admin/spread/elchee/orders?c.bc={$v['infoid']}' class='dialogit'>查看</a>";
                    }elseif($v['yjtype'] == 1){
                        $list[$k]['no'] = "<a href='/admin/spread/elchee/regdomain?taskid={$v['infoid']}' class='dialogit'>查看</a>";
                    }
                }else{
                    $list[$k]['no'] = "--";
                }
                // 充值金额
                $list[$k]['f.type'] = $fun->getStatus($v['type'],['推广系统','怀米大使','分销系统']);
                $list[$k]['f.status'] = $fun->getStatus($v['status'],['未申请','提取中','提取成功']);
                $list[$k]['f.yjtype'] = $fun->getStatus($v['yjtype'],['域名交易','域名注册']);
                $list[$k]['cji'] = $yj;
                $list[$k]['zje'] = $zje;
                $list[$k]['f.yj'] = $v['yj'];
                $list[$k]['u1.uid'] = $v['uuid'];
                $list[$k]['u.uid'] = $v['uid'];
                $list[$k]['f.time'] = $v['time'];
                $list[$k]['f.updatetime'] = $v['updatetime'];
                $list[$k]['f.apptime'] = $v['apptime'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('id',$this->request->get('id'));
        return $this->view->fetch();
    }
}


