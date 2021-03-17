<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
/**
 * 后缀设置
 *
 * @icon fa fa-user
 */
class Regapi extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;
    protected $redis = null;
    protected $noNeedRight = ['getcate','getRegisterUserName'];

    public function _initialize()
    {   
        parent::_initialize();
        $this->model = Db::name('domain_api');
        $this->redis = new Redis();
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
            $total = $this->model->where($where)->count();
            $list = $this->model->field('tempid,tit,emailau,ifreal,regid,status,id,xf_lock,showtit') 
                    ->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            $cates = $this->getCates();
            foreach($list as $k=>&$v){
                $v['regid'] =  $cates[$v['regid']];
                $v['xf_lock'] = $fun->getStatus($v['xf_lock'],['正常','禁止']);
                $v['status'] = $fun->getStatus($v['status'],['无效状态','启用','禁用']);
                $v['emailau'] = $fun->getStatus($v['emailau'],['不需要','需要']);
                $v['ifreal'] = $fun->getStatus($v['ifreal'],['--','不需要','需要']);
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
        // $test = $this->redis->lrange('Api_Id',0,-1);
        // echo '<pre>';
        // foreach($test as $k=>$v){
        //     print_R($this->redis->hgetall('Api_Info_'.$v));
        // }
        // die;
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                $params['createtime'] = time();
                $id = $this->model->insertGetId($params);
//                if($params['status'] == 1){
                    $params['id'] = $id;
                    //获取注册商名字
                    $zcslist = $this->getCates();
                    $params['regname'] = $zcslist[$params['regid']];
                    $this->saveApis($params);
                    $this->redis->lRem('Api_Id',0,$id);
                    $this->redis->RPush('Api_Id',$id);
                    $this->redis->hMset('Api_Info_'.$id,$params);
//                }
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $this->view->assign('cat',$this->getCates('api',false));
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
                $this->model->update($params);
                $this->redis->lRem('Api_Id',0,$params['id']);
                $this->redis->del('Api_Info_'.$params['id'],$params);
//                if($params['status'] == 1){
//                    获取注册商名字
                    $zcslist = $this->getCates();
                    $params['regname'] = $zcslist[$params['regid']];
                    $this->saveApis($params);
                    $this->redis->RPush('Api_Id',$params['id']);
                    $this->redis->hMset('Api_Info_'.$params['id'],$params);
//                }
                $this->success('修改成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->where(['id'=>$ids])->find();
        $this->view->assign('data',$data);
        $this->view->assign('cat',$this->getCates('api',false)); 
        return $this->view->fetch();
    }
    
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            if(strpos($ids,',')){
                $arr =  explode(',',$ids);
                $this->saveApis([],$arr);
                foreach($arr as $k=>$v){

                    $this->redis->lRem('Api_Id',0,$v);
                    $this->redis->del('Api_Info_'.$v);    
                }
            }else{
                $this->saveApis([],[$ids]);
                $this->redis->lRem('Api_Id',0,$ids);
                $this->redis->del('Api_Info_'.$ids);    
            }
            $this->model->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
    }
    // 获取分类
    public function getcate(){
       return parent::getcategory();
    }
    //获取接口商 下拉框
    public function getRegisterUserName(){
       $data = $this->getApis();
       return $data;
    }

}
