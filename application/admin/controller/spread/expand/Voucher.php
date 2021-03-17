<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use think\Db;
use think\Validate;
use app\admin\common\Fun;

/**
 * 代金券
 *
 * @icon fa fa-user
 */
class Voucher extends Backend
{

    protected $relationSearch = false;
    protected $fun = null;
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
            $total = $this->model
                    ->alias('v')
                    ->join('domain_user u','u.id=v.userid')
                    ->join('admin a','v.topid=a.id')
                    ->where($where)
                    ->count();
            $list = $this->model
                    ->alias('v')
                    ->join('domain_user u','u.id=v.userid')
                    ->join('admin a','v.topid=a.id')
                    ->field('v.*,u.uid,a.nickname')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm1 = 'SELECT sum(money) as n,sum(kyye) as f FROM '.PREFIX.'domain_voucher ';
            }else{
                $conm1 = 'SELECT sum(v.money) as n,sum(v.kyye) as f FROM '.PREFIX.'domain_voucher as v left join '.PREFIX.'domain_user as u on v.userid=u.id left join '.PREFIX.'admin as a on v.topid=a.id'.$sql;
            }
            $res1 = Db::query($conm1);
            $sj = time();
            $fun = Fun::ini();
            $cates = $this->getCates('voucher');
            foreach($list as $k => $v){
                $list[$k]['sycp'] = $cates[$v['sid']];;
                $list[$k]['cjid_text'] = $cates[$v['cjid']];
                $list[$k]['v.status'] = $fun -> getStatus($v['status'],['未审核','审核成功','审核失败','已禁用']);
                if(empty($v['audittime'])){
                    $list[$k]['sxsj'] = '';    
                }else{
                    $list[$k]['sxsj'] = $v['audittime'] + $v['cycletime'];
                }
                $list[$k]['v.money'] = $v['money'];
                $list[$k]['v.createtime'] = $v['createtime'];
                $list[$k]['remark'] = $fun -> returntitdian($v['remark'],20);
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
                $list[$k]['mezje'] = $res1[0]['n'];
                $list[$k]['kyje'] =  $res1[0]['f'];
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
                        'row[uid]' => 'require',
                        'row[cycletime]'=>'require',
                        'row[audittime]'=>'require',
                    ];
                    $msg = [
                        'row[money].require' => '必须填写代金券面额',
                        'row[money].number'   => '请输入有效数字',
                        'tyid.require'       => '请选择使用场景',
                        'row[uid].require'=> '请选择用户',
                        'row[cycletime].require'=>'请选择有效周期',
                        'row[audittime].require'=>'请选择生效时间',
                    ];
                    $data = [
                        'row[money]'  => $params['money'],
                        'tyid'        => $this->request->post('tyid'),
                        'row[uid]' => $params['uid'],
                        'row[cycletime]'=>$params['cycletime'],
                        'row[audittime]'=>$params['audittime'],
                    ];
                    $validate = new Validate($rule, $msg);
                    if(!$validate->check($data)){
                        $this->error($validate -> getError());
                    }

                    $params['userid'] = Db::name('domain_user')->where('uid',$params['uid'])->value('id');
                    if(empty($params['userid'])){
                        $this->error('用户名不存在');
                    }
                    unset($params['uid']);
                    $sj = time();
                    $params['bh'] = $this -> auth -> id.$sj.$params['userid'];
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
        $this -> view -> assign('sel',$sel); 
        return $this->view->fetch();
    }


    /**
     * 申请代金券
     */
    public function edit($ids=''){
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                //规则验证
                    $rule = [
                        'row[status]'        => 'require',
                    ];
                    $msg = [
                        'row[status].require'=> '请选择审核状态',
                    ];
                    $data = [
                        'row[status]'        => $params['status'],
                    ];
                    $validate = new Validate($rule, $msg);
                    if(!$validate->check($data)){
                        $this->error($validate -> getError());
                    }
                $params['actime'] = time();
                //插入流水表
                $userid =  intval($this->request->post('userid'));
                //查询金额
                $mm = $this -> model -> field('money') -> find($params['id']);
                if($userid == 0 || !$mm){
                    return $this -> error('参数无效');
                }
                $this->model->update($params); 
                if($params['status'] == 1){
                    Db::name('domain_voucherrecord')-> insert(['voucher_id'=>$params['id'],'addmoney'=>$mm['money'],'remark'=>'系统发放','userid'=>$userid,'createtime'=>$params['actime']]);
                }
                return $this -> success('申请成功,审核成功后开始生效！');
            }
        }
        $data = $this->model->find($ids);
         
        //获取发送人
        $user =  Db::name('domain_user')->where('id',$data['userid'])->value('uid');

        $data['username'] = $user;
        
        $cp = Db::name('category')->where('id',$data['sid'])->value('name');
        $cj = Db::name('category')->where('id',$data['cjid'])->value('name');
        $data['cp'] = $cp;
        $data['cj'] = $cj;
       
        //格式化周期 
        $data['zq'] = $data['cycletime'] / 86400 ;
        $this -> view -> assign('data',$data);
        return $this->view->fetch();
    }
    //禁用按钮
    public function disable($ids='')
    {
        $this -> request -> filter(['strip_tags']);
        if(empty($ids)){
            return $this -> error('请点击下刷新按钮再禁用');
        }
        $this -> model -> where(['id'=>$ids]) -> update(['status'=>3]);

        return json_encode(['msg'=>'操作成功','code'=>1]);
        // return $this -> success('操作成功');

    }

}
