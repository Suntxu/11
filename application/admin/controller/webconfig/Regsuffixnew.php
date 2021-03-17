<?php
namespace app\admin\controller\webconfig;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 注册 新版本
 *
 * @icon fa fa-user
 */
class Regsuffixnew extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;
    protected $noNeedRight = ['getRegisterApis'];

    public function _initialize()
    {   
        parent::_initialize();
        $this->model = Db::name('regsuffix_config');
    }
    
    /**
     * 查看
     */
    public function index()
    {   
        //设置过滤方法
        if ($this->request->isAjax())
        {   
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $time = time();
            if(empty($group)){
                $def = '';
            }elseif($group == 1){ // 未开始
                $def = ' r.stime > '.$time;
            }elseif($group == 2){ // 进行中
                $def = ' r.stime < '.$time.' and r.etime > '.$time;
            }elseif($group == 3){ // 已结束
                $def = ' r.etime < '.$time;
            }

            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id','left')->where($where)->where($def)->count();
            $list = $this->model->alias('r')->join('domain_user u','r.userid=u.id','left')
                    ->field('r.suffix,r.stime,r.etime,r.money,r.id,u.uid,r.create_time,r.status,r.userid,r.aid')
                    ->where($where)->where($def)->order($sort,$order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            $apis = $this->getApis(-1);
            foreach($list as $k=>$v){
                $list[$k]['status'] = $fun->getStatus($v['status'],['启用','禁用']);
                $list[$k]['show'] = '查看';
                $list[$k]['wtime'] = date('Y-m-d H:i:s',$v['stime']).' - '.date('Y-m-d H:i:s',$v['etime']);
                //状态选择
                if($v['stime'] > $time){
                    $list[$k]['group'] = '<span style="color:gray">未开始</span>';
                }elseif($v['stime'] < $time && $v['etime'] > $time){
                    $list[$k]['group'] = '<span style="color:red">进行中</span>';
                }else{
                    $list[$k]['group'] = '<span style="color:green">已结束</span>';
                }
                if($v['aid'] == 0){
                    $list[$k]['zcs'] = '--';
                    $list[$k]['aid'] = '--';
                }else{
                    $list[$k]['zcs'] = $apis[$v['aid']]['regname'];
                    $list[$k]['aid'] = $apis[$v['aid']]['tit'];
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['money']) || empty($params['suffix']) || empty($params['uid']) || empty($params['etime']) || empty($params['stime']) || empty($params['reg'])){
                    return $this->error('请输入必填项');
                }
                
                $userid = Db::name('domain_user')->where('uid',$params['uid'])->value('id');
                
                if(empty($userid)){
                    $this->error('输入的用户不存在');
                }
                
                $zinfo = explode('_',$params['reg']);
                
                unset($params['reg']);

                $params['zcs'] = $zinfo[0];
                $params['aid'] = $zinfo[1];

                //判断每个用户每个后缀每个注册商只能有一个
                $flag = $this->model->where(['userid' => $userid,'suffix' => $params['suffix'],'zcs' => $params['zcs']])->count(0);
                if($flag){
                    $this->error('请不要重复在同一个用户下面添加相同后缀的接口商');
                
                }
                $params['etime'] = strtotime($params['etime']);
                $params['stime'] = strtotime($params['stime']);

                if($params['etime'] <=  $params['stime']){
                    $this->error('结束时间必须比开始时间日期大');
                }

                $params['userid'] = $userid;
                $params['create_time'] = time();
                unset($params['uid']);
                $this->model->insert($params);
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        
        //获取可用的后缀
        $suffixList = Db::name('register_suffix_config')->where('zt',1)->group('name')->column('name');
        $this->view->assign('suffix',$suffixList);
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                $lind = $this->request->post('suffix_old');
                if(empty($params['money'])){
                    return $this->error('请输入必填项');
                }
                $params['etime'] = strtotime($params['etime']);
                // $params['stime'] = strtotime($params['stime']);
                $this->model->update($params);
                $this->success('修改成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->alias('r')->join('domain_user u','r.userid=u.id','left')->field('r.suffix,r.stime,r.etime,r.money,r.id,r.status,u.uid,r.aid')->where(['r.id'=>$ids])->find();

        $apis = $this->getApis(-1);
        $data['aid'] = $apis[$data['aid']]['regname'].'--'.$apis[$data['aid']]['tit'];
         //获取可用的后缀
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
    
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this->model->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
    }

    /**
     * 获取后缀注册商
     */
    public function getRegisterApis(){
        
        if($this->request->isAjax()){

            $hz = $this->request->post('hz');

            $time = time();        
            //获取已有的api
            $yzcs = $this->model->where(' etime >= '.$time.' and suffix = "'.$hz.'" ')->column('zcs');

            //查询可用的注册商
            $info = Db::name('register_suffix_config')->where(['zt' => 1,'name' => $hz])->where('zcs','not in',$yzcs)->column('aid');

            $apis = $this->getApis(-1);

            $data = [];

            foreach($info as $k => $v){
                $data[$k]['id'] = $apis[$v]['regid'].'_'.$v;
                $data[$k]['name'] = $apis[$v]['regname'].'--'.$apis[$v]['tit'];
            }
            return ['code' => 0,'data' => $data];
        }
    }


}
