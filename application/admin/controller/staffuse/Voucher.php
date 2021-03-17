<?php

namespace app\admin\controller\staffuse;

use app\common\controller\Backend;
use think\Db;
use think\Validate;
use app\admin\common\Fun;
// use fast\Tree;

/**
 * 代金券
 *
 * @icon fa fa-user
 */
class Voucher extends Backend
{

    /**
     * UserÄ£ÐÍ¶ÔÏó
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_voucher');
    }

    /**
     * ²é¿´
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $wh = ['v.topid'=>$this->auth->id];
            $total = $this->model->alias('v')->join('domain_user u','u.id=v.userid')->where($where)->where($wh)->count();
            $list = $this->model->alias('v')->join('domain_user u','u.id=v.userid')->field('v.*,u.uid')
                    ->where($where)->where($wh)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $sql = $this -> setWhere();
            $sj = time();
            if(strlen($sql) == 12){
                $conm1 = 'SELECT sum(money) as n,sum(kyye) as f FROM '.PREFIX.'domain_voucher WHERE topid = '.$this->auth->id;
            }else{
                $conm1 = 'SELECT sum(v.money) as n,sum(v.kyye) as f FROM '.PREFIX.'domain_voucher as v left join '.PREFIX.'domain_user as u on v.userid=u.id '.$sql.' and v.topid= '.$this->auth->id;
            }
            $res1 = Db::query($conm1);
            $fun = Fun::ini();
            $cates = $this->getCates('voucher');
            foreach($list as $k => $v){
                $list[$k]['sycp'] = $cates[$v['sid']];;
                $list[$k]['cjid_text'] = $cates[$v['cjid']];
                $list[$k]['status'] = $fun->getStatus($v['status'],['未审核','审核成功','审核失败','已禁用']);
                $list[$k]['audit_remark'] = $fun->returntitdian($v['audit_remark'],20);
                if(empty($v['audittime'])){
                    $list[$k]['sxsj'] = '';    
                }else{
                    $list[$k]['sxsj'] = $v['audittime'] + $v['cycletime'];
                }
                $list[$k]['mezje'] = $res1[0]['n'];
                $list[$k]['kyje'] =  $res1[0]['f'];
                //使用状态
                if(!empty($v['audittime']) && ($v['audittime']+$v['cycletime']) < $sj ){
                    $list[$k]['sjstat'] = '已过期';
                }else{
                    if($v['status'] == 1 && $v['kyye'] != 0){
                        $list[$k]['sjstat'] = '可使用';
                    }elseif(($v['status'] == 0 || $v['status'] == 2 || $v['status'] == 3) && $v['kyye'] != 0){
                        $list[$k]['sjstat'] = '不可用';
                    }elseif($v['status'] == 1 && $v['kyye'] == 0){
                        $list[$k]['sjstat'] = '已用完';
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 申请代金券
     */
    public function add(){
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            $sj = time();
            if ($params){
                //规则验证
                    $rule = [
                        'row[money]'  => 'require|number',
                        'tyid'        => 'require',
                        'row[userid]' => 'require',
                        'row[cycletime]'=>'require',
                        'row[audittime]'=>'require',
                    ];
                    $msg = [
                        'row[money].require' => '必须填写代金券面额',
                        'row[money].number'   => '请输入有效数字',
                        'tyid.require'       => '请选择使用场景',
                        'row[userid].require'=> '请选择用户',
                        'row[cycletime].require'=>'请选择有效周期',
                        'row[audittime].require'=>'请选择生效时间',
                    ];
                    $data = [
                        'row[money]'  => $params['money'],
                        'tyid'        => $this->request->post('tyid'),
                        'row[userid]' => $params['userid'],
                        'row[cycletime]'=>$params['cycletime'],
                        'row[audittime]'=>$params['audittime'],
                    ];
                    $validate = new Validate($rule, $msg);
                    if(!$validate->check($data)){
                        $this->error($validate -> getError());
                    }
                    $sj = time();
                    $params['bh'] = $this->auth->id.$sj.$params['userid'];
                    $params['money'] = sprintf("%'01.2f",$params['money']);
                    $params['kyye']=$params['money'];
                    //失效周期
                    switch($params['cycletime']){
                        case 'week':
                                $params['cycletime'] = 86400*7;
                            break;
                        case 'mon':
                                $params['cycletime'] = 86400*15;
                            break;
                        case 'month':
                                $params['cycletime'] = 86400*30;
                            break;
                        case 'quarter':
                                $params['cycletime'] = 86400*90;
                            break;
                        case 'year':
                                $params['cycletime'] = 86400*180;
                            break;
                    }
                    $params['createtime'] = $sj;
                    $params['audittime'] =  empty($params['audittime'])? '' : strtotime($params['audittime']);
                   //适用 场景
                   $ar = explode('@~@',$this->request->post('tyid'));
                   if(count($ar) == 2){
                        $params['sid'] = $ar[1];
                        $params['cjid'] = $ar[0];
                   }else{
                        $this->error('适用场景参数有误');
                  }
                  $params['topid'] = $this->auth->id;
                 $this->model->insert($params); 
                 return $this -> success('申请成功,审核成功后开始生效！');
            }else{
                $this -> error('错误参数');
            }
        }
        //产品下拉框
        $ruledata = Db::name('category') -> field('id,name,pid') -> where(['status'=>'normal','type'=>'voucher']) -> select();
        $sel = "<select class='form-control' name='tyid'>";
        foreach($ruledata as $k => $v){
            //父类ID
            if($v['pid'] != 0){
                continue;
            }else{
                $sel.="<option value='0@~@{$v['id']}' disabled >{$v['name']}</option>";
                foreach($ruledata as $kk => $vv ){
                    if($vv['pid'] == 0){
                        continue;
                    }else{
                        $sel.="<option value='{$vv['id']}@~@{$vv['pid']}'>&nbsp;&nbsp;--{$vv['name']}</option>";
                    }
                    
                }
            }
        }
        $sel.="</select>";
        $this->view->assign('sel',$sel); 
        return $this->view->fetch();
    }
            /**
     * 申请代金券
     */
    public function edit($ids=''){
        $data = $this->model->find($ids);
        if ($this->request->isPost()){
            //如果状态是审核失败的 不能删除
            if($data['status'] == 1){
                return $this ->error('审核成功后不能做任何操作');
            }elseif($data['status'] == 3){
                return $this ->error('已被禁止的申请不能做任何操作');
            }
            $params = $this->request->post("row/a");
            if ($params){
                //规则验证
                    $rule = [
                        'tyid'        => 'require',
                        'row[userid]' => 'require',
                        'row[cycletime]'=>'require',
                    ];
                    $msg = [
                        'tyid.require'       => '请选择使用场景',
                        'row[userid].require'=> '请选择用户',
                        'row[cycletime].require'=>'请选择有效周期',
                    ];
                    $data = [
                        'tyid'        => $this->request->post('tyid'),
                        'row[userid]' => $params['userid'],
                        'row[cycletime]'=>$params['cycletime'],
                    ];
                    $validate = new Validate($rule, $msg);
                    if(!$validate->check($data)){
                        $this->error($validate -> getError());
                    }
                    //失效周期
                    switch($params['cycletime']){
                        case 'week':
                                $params['cycletime'] = 86400*7;
                            break;
                        case 'mon':
                                $params['cycletime'] = 86400*15;
                            break;
                        case 'month':
                                $params['cycletime'] = 86400*30;
                            break;
                        case 'quarter':
                                $params['cycletime'] = 86400*90;
                            break;
                        case 'year':
                                $params['cycletime'] = 86400*180;
                            break;
                    }
                   //适用 场景
                   $ar = explode('@~@',$this->request->post('tyid'));
                   if(count($ar) == 2){
                        $params['sid'] = $ar[1];
                        $params['cjid'] = $ar[0];
                   }else{
                        $this->error('适用场景参数有误');
                  }
                 $params['status'] = 0;
                 $params['createtime'] = time();
                 $params['audit_remark'] = '';
                 $this->model->update($params); 
                 return $this -> success('申请成功,审核成功后开始生效！');
            }
        }
        //产品下拉框
        $ruledata = Db::name('category') -> field('id,name,pid') -> where(['status'=>'normal','type'=>'voucher']) -> select();
        $sel = "<select class='form-control' name='tyid'>";
        foreach($ruledata as $k => $v){
            //父类ID
            if($v['pid'] != 0){
                continue;
            }else{
                $sel.="<option value='0@~@{$v['id']}' disabled >{$v['name']}</option>";
                foreach($ruledata as $kk => $vv ){
                    if($vv['pid'] == 0){
                        continue;
                    }else{
                        if($data['cjid'] == $vv['id']){
                            $sel.="<option selected value='{$vv['id']}@~@{$vv['pid']}'>&nbsp;&nbsp;--{$vv['name']}</option>";
                        }else{
                            $sel.="<option value='{$vv['id']}@~@{$vv['pid']}'>&nbsp;&nbsp;--{$vv['name']}</option>";
                        }
                       
                    }
                    
                }
            }
        }
        $sel.="</select>";
        //获取发送人
        $data['username'] = Db::name('domain_user')->where('id',$data['userid'])->value('uid');
        //格式化周期 
        $data['zq'] = $data['cycletime'] / 86400 ;
        $this->view->assign(['sel'=>$sel,'data'=>$data]);
        return $this->view->fetch();
    }
    //删除
    public function del($ids='')
    {
        //如果状态是审核失败的 不能删除
        $sta = $this->model->where('id in ('.$ids.')')->field('id,status')->select();
        $id = '';
        foreach($sta as $k => $v){
            if($v['status'] == 0){
                $id .= $v['id'].',';
            }
        }
        if($id ==''){
            return $this -> error('只有未审核的申请才能删除');
        }

        $this->model->where('id in ('.rtrim($id,',').')')->delete();
        return $this->success('未审核的申请删除成功');
    }

}
