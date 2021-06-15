<?php

namespace app\admin\controller\oprecord;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 已成交域名列表
 * @icon fa fa-user
 */
class Delrecord extends Backend
{
    protected $noNeedRight = ['show'];
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_operate_record');
    }
    /**
     * 查看
     */
    public function index(){
        if ($this->request->isAjax()){
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            
            $def = '';
            if($group){
                $def = ' FIND_IN_SET("'.trim($group).'",r.tit) or r.tit like "%'.trim($group).'%" ';
            }
            $total = $this->model->alias('r')->join('admin a','a.id=r.operator_id')->where($where)->where($def)->count();
            $list = $this->model->alias('r')->join('admin a','a.id=r.operator_id')->field('r.id,r.create_time,a.nickname,r.type,r.value')
                    ->where($where)->where($def)->order($sort,$order)->limit($offset,$limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                if(in_array($v['type'],[3,10])){
                    $value = explode('：',$v['value']);
                    $ddbh = empty($value[1]) ? '' :trim($value[1]);
                    if($v['type'] == 3){
                        $url = '/admin/vipmanage/recode/payrank?d.sj= &ddbh='.$ddbh;
                    }else if($v['type'] == 10){
                        $url = '/admin/vipmanage/recode/deallog?c.sj= &oid='.$ddbh;
                    }
                    $v['tit1'] = "<a href='{$url}' data-toggle='tooltip' class='dialogit'>详情</a>";
                }elseif(in_array($v['type'],[0,1,4,6,7,8])){
                    $v['tit1'] = '<span  style="cursor:pointer;color:#3c8dbc;" data-id="'.$v['id'].'" onclick="showDetail(this)">查看</span>';
                }elseif($v['type'] == 5){
                    $v['tit1'] = "<a href='/admin/vipmanage/recode/refund?oid={$v['id']}' data-toggle='tooltip' class='dialogit'>详情</a>";
                }else{
                    $v['tit1'] = '--';
                }
                if(mb_strlen($v['value']) > 15){
                    $v['value'] = $fun->returntitdian($v['value'],15).'<span class="show_value" style="cursor:pointer;color:#3c8dbc;" onclick="showRemark(\''.$v['value'].'\')" >查看</span>';
                }
                $v['type'] = $fun->getStatus($v['type'],['域名出库','冻结操作','修改微信cookie','手动补单','域名入库','注册域名退款','修改一口价属性','手动过户','域名续费','解除异地限制','一口价退款']);


            }
            return json(['total'=>$total,'rows'=>$list]);
        }
        $this->view->assign([
            'type' => $this->request->get('type',''),
            'id' => $this->request->get('id'),//资金明细
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
            $tits = $this->model->where('id',$id)->value('tit');
            return ['code' => 0,'msg' => 'success','data' => $tits];

        }

    }


}
