<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 充值记录
 *
 * @icon fa fa-user
 */
class Payrank extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_dingdang');
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
            $total = $this->model->alias('d')->join('domain_user u','d.userid=u.id')
                          ->where($where)->count();
            $list = $this->model->alias('d')->join('domain_user u','d.userid=u.id')
                    ->field('d.id,d.remark,d.ddbh,d.money1,d.sj,d.ifok,d.userid,u.uid,d.bz,d.wxddbh,d.uip')
                    ->where($where)->order($sort, $order)->limit($offset, $limit)
                    ->select();
            //总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang';
                $conm1 = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang where ifok = 1';
            }else{
                $conm = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_dingdang as d left join '.PREFIX.'domain_user as u on d.userid=u.id '.$sql;
                $conm1 = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_dingdang as d left join '.PREFIX.'domain_user as u on d.userid=u.id '.$sql.' AND d.ifok = 1 ';
            }
            $res = Db::query($conm);
            $res1 = Db::query($conm1);
            if(empty($res1[0]['n'])){
              $res1[0]['n'] = 0;
            }
            $date = date('Y-m-d H:i:s',strtotime('-1 hour'));
            foreach($list as $k => $v){
               $list[$k]['zje'] = $res[0]['n'];
               $list[$k]['ifok'] = Fun::ini()->getStatus($v['ifok'],['失败','成功']);
               $list[$k]['d.sj'] = $v['sj'];
               $list[$k]['d.money1'] = $v['money1'];
               $list[$k]['suc'] = $res1[0]['n'];
               if($v['remark']){
                  $list[$k]['d.remark'] = '<font style="color:red">'.$v['remark'].'</font>';
               }else{
                  $list[$k]['d.remark'] = '';
               }
               if($v['ifok'] == '失败' && $date < $v['sj']){
                $list[$k]['op'] = '<button type="button" onclick="bd(this)" data-url="/admin/vipmanage/recode/payrank/show" data-id="'.$v['id'].'" class="btn btn-xs btn-warning btn-magic" data-title="请输入交易号" title="补单" data-table-id="table"><i class="fa fa-pencil"></i></a>';
              }else{
                $list[$k]['op'] = '';
              }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign([
          'ids' => $this->request->get('uid'),
          'id'  => $this->request->get('id'),
          'ddbh'=> $this->request->get('ddbh'),
        ]);
        return $this->view->fetch();
    }
    // 补单
    public function show($ids=''){
        $id = $this->request->post('id',0);
        $number = $this->request->post('number','');
        if(empty($id) || empty($number)){
          return ['code' => 1 ,'msg' => '缺少重要参数'];
        }
        $data = $this->model->field('id,money1,sj,uip,userid,ddbh')->where(['id' => $id,'ifok' => 0])->find();
        if(empty($data)){
          return ['code' => 1, 'msg' => '订单可能已经更新成功,请刷新！'];
        }
        // 插入 补单状态
        Db::startTrans();
          $u1 = Db::name('domain_user')->where(['id' => $data['userid']])->setInc('money1',$data['money1']);
          $blace = Db::name('domain_user')->where(['id' => $data['userid']])->value('money1');
          // 修改订单表
          $u2 = Db::name('domain_dingdang')->where(['id' => $data['id']])->update(['ifok' => 1,'wxddbh' => $number,'remark' => '手动补单-交易号:'.$number]);
          // 资金明细
          $u3 = Db::name('flow_record')->insert([
                  'infoid' => $data['id'],
                  'userid' => $data['userid'],
                  'product' => 2,
                  'subtype' => 4,
                  'sj'=>date('Y-m-d H:i:s'),
                  'uip' => $data['uip'],
                  'money' => $data["money1"],
                  'balance' => $blace,
              ]);
          //插入后台操作表
          $u4 = Db::name('domain_operate_record')->insert(['tit' => '充值补单','operator_id'=>$this->auth->id,'create_time'=>time(),'type' => 3,'value' => '充值订单交易号：'.$data['ddbh']]);

          if($u1 == $u2 && $u2 == $u3 && $u3 == $u4){
            Db::commit(); 
            return ['code' => 0, 'msg' => '补单成功!'];
          }else{
            Db::rollback();
            return ['code' => 1, 'msg' => '补单失败！'];
          }

        return $this->view->fetch();

    }


}


