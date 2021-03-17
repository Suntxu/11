<?php
namespace app\admin\controller\spread;
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
    protected $noNeedRight = ['getuserinfo'];
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
           
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('spreader_flow')->alias('f')->join('domain_user u','f.userid=u.id')->join('domain_user u1','u1.id=f.buyeruserid')
                    ->where($where)
                    ->count();
            $list = Db::name('spreader_flow')->alias('f')->join('domain_user u','f.userid=u.id')->join('domain_user u1','u1.id=f.buyeruserid')
                    ->field('u1.uid as uuid,f.type,f.infoid,u.uid,f.paymoney,f.status,f.time,f.updatetime,f.yjtype,f.apptime,f.yj,f.buyeruserid,f.extra_sxf,f.source')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(paymoney) as n, sum(yj) as yj FROM '.PREFIX.'spreader_flow';
            }else{
                $conm = 'SELECT sum(f.paymoney) as n,sum(f.yj) as yj FROM '.PREFIX.'spreader_flow f inner join  '.PREFIX.'domain_user u on u.id=f.userid inner join '.PREFIX.'domain_user u1 on f.buyeruserid=u1.id '.$sql;
            }
            $res = Db::query($conm);
            $zje = sprintf('%.2f',$res[0]['n']);
            $yj = sprintf('%.2f',$res[0]['yj']);
            //根据条件统计总金额
            $fun = Fun::ini();
            foreach($list as $k => $v){
                if($v['yjtype'] == 0){
                    $list[$k]['uip'] = Db::name('domain_order')->where('bc',$v['infoid'])->value('uip');
                    if($v['type'] == 1){
                        if($v['source'] == 0){
                            $list[$k]['no'] = "<a href='/admin/spread/elchee/orders?c.bc={$v['infoid']}' class='dialogit'>查看</a>";
                        }else{
                            $list[$k]['no'] = "<a href='/admin/spread/elchee/Ordersagentout?c.bc={$v['infoid']}' class='dialogit'>查看</a>";
                        }
                    }elseif($v['type'] == 2){
                        $list[$k]['no'] = "<a href='/admin/vipmanage/recode/deallog?bc={$v['infoid']}' class='dialogit'>查看</a>";
                    }else{
                        $list[$k]['no'] = "<a href='/admin/spread/expand/proindent?bc={$v['infoid']}' class='dialogit'>查看</a>";
                    }
                }elseif($v['yjtype'] == 1){
                    $list[$k]['uip'] = Db::table(PREFIX.'Task_record')->where('id',$v['infoid'])->value('uip');
                    if($v['type'] == 1){
                        $list[$k]['no'] = "<a href='/admin/spread/elchee/regdomain?taskid={$v['infoid']}' class='dialogit'>查看</a>";
                    }else{
                        $list[$k]['no'] = "<a href='/admin/spread/expand/regdomain?taskid={$v['infoid']}' class='dialogit'>查看</a>";
                    }
                }

                // 充值金额
                $list[$k]['f.type'] = $fun->getStatus($v['type'],['推广系统','怀米大使','分销系统']);
                $list[$k]['f.status'] = $fun->getStatus($v['status'],['未申请','提取中','提取成功']);
                $list[$k]['f.yjtype'] = $fun->getStatus($v['yjtype'],['域名交易','域名注册','拼团返点','域名预定返点']);
                $list[$k]['f.source'] = $fun->getStatus($v['source'],['怀米网','外部订单']);
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
     // 获取用户的公共信息
    public function getuserinfo(){
        $ids = $this->request->get('id');
        $info = Db::name('domain_user')->field('uid,sj,money1,baomoney1,mot,uqq,zt,special,uip,weixin,baomoney1')->where('id',$ids)->find();
        $info['zt'] = Fun::ini()->getStatus($info['zt'],[1 => '正常','邮箱未激活','禁用','安全码错误过多']);
        $this->view->assign('info',$info);
        return $this->view->fetch();
    }
}
