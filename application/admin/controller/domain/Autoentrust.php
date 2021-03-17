<?php

namespace app\admin\controller\domain;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 自动委托购买域名
 *
 * @icon fa fa-user
 */
class Autoentrust extends Backend
{
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_auto_entrust');
    }
    /**
     * 查看
     */
    public function index(){

        if ($this->request->isAjax()){
        
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = $this->model->alias('a')->join('domain_user u','a.userid = u.id')->where($where)->count();

            $list = $this->model->alias('a')->join('domain_user u','a.userid = u.id')
                    ->field('a.id,a.title,a.end_time,a.expire,a.count,a.money,a.status,a.create_time,a.finish_time,a.sale_type,a.buy_count,a.minmoney,a.suffix,a.uip,u.uid')
                    ->where($where)->order($sort,$order)->limit($offset,$limit)
                    ->select();
            // 已支付金额  总金额
            $ids = array_column($list,'id');
            $stotals = Db::name('domain_baomoneyrecord')->where(['status' => 0,'type' => 13])->whereIn('infoid',$ids)->group('infoid')->column('sum(moneynum)','infoid');
            $ytotals = Db::name('domain_baomoneyrecord')->where(['status' => 2,'type' => 13])->whereIn('infoid',$ids)->group('infoid')->column('sum(moneynum)','infoid');

            $fun = Fun::ini();

            $time = time();
           
            foreach($list as &$v){

                $syj =  isset($stotals[$v['id']]) ? sprintf('%.2f',$stotals[$v['id']]) : 0;  //剩余
                $yje =  isset($ytotals[$v['id']]) ? sprintf('%.2f',$ytotals[$v['id']]) : 0;  //剩余
                $zje = $v['money'] * $v['count']; //总金额
                //退还金额
                // $v['dtotal'] = 0;
                // if($v['status'] != 0){
                //     $v['dtotal'] = $v['ztotal'] - $v['ytotal'];
                // }
                if($v['end_time'] < $time && $v['end_time'] != 0 && $v['status'] == 0){
                    $v['status'] = 3;
                }
                $v['end_time'] = $v['end_time'] == 0 ? '不限' : date('Y-m-d H:i:s',$v['end_time']);
                $v['count'] = '<span style="color:red">'.$v['buy_count'].'</span>/'.$v['count'];
                $v['ztotal'] = '<span style="color:red">'.$zje.'</span>/<span style="color:orange">'.$syj.'</span>/'.'<span style="color:green;">'.$yje.'</span>';
                $v['a.status'] = $fun->getStatus($v['status'],['<span style="color:red;">进行中</span>','<span style="color:green;">已完成</span>','<span style="color:gray;">已取消</span>','<span style="color:orange;">已过期</span>']);
                $v['a.expire'] = $fun->getStatus($v['expire'],['不限','1~3个月','3~6个月','6~12个月','12月以上']);
                $v['sale_type'] = $fun->getStatus($v['sale_type'],['一口价域名','打包一口价域名']);
                $v['show'] = '查看';
                $v['money'] = $v['minmoney'].' - '.$v['money'];
                


            }
            return json(['total'=>$total,'rows'=>$list]);
        }
        $this->view->assign('id',$this->request->get('id','')); //保证金列表连接
        return $this->view->fetch();
    }

}
