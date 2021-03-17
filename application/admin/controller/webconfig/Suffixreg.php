<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 后缀注册注册商
 *
 * @icon fa fa-user
 */
class Suffixreg extends Backend
{
    protected $noNeedRight = ['getZcsOption','getApisOption'];
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('register_suffix_config');
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
            $field = ',(select sum(success_num)  from '.PREFIX.'reg_discount where hz = name and aid = aid and time between yhsj1 and yhsj2  ) as snum';
            $list = $this->model->field('id,name,money,defu,ysje,yhsj1,yhsj2,num,money1,num1,money2,num2,aid,regbrokerage,discounts,cost,zt'.$field)
                ->where($where)
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            
            $fun = Fun::ini();
            $apis = $this->getApis(-1);
           
            foreach($list as &$v){
                if($v['defu'] == 1){
                    $v['name'] = $v['name'].' <span style="color:red;font-size: 8px;">默认</span>';
                }
                $v['zt'] = $fun->getStatus($v['zt'],['--','已启用','已禁用']);
                $v['zcs'] = $apis[$v['aid']]['regname'];
                $v['aid'] = $apis[$v['aid']]['tit'];
                $v['snum'] = ($v['discounts'] == 0) ? 0 : $v['discounts'] - $v['snum'];
            }
            $result = array("total" =>$total, "rows" => $list);
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

            $params = $this->request->post('row/a');
            
            if( empty($params['name']) || empty($params['zcs']) || empty($params['ysje']) || empty($params['aid']) ){
                $this->error('请填写必要的参数');
            }
            if($params['yhsj1']){
                $params['yhsj1'] = strtotime($params['yhsj1']);
            }
            if($params['yhsj2']){
                $params['yhsj2'] = strtotime($params['yhsj2']);
            }
            $count = $this->model->where(['name' => $params['name'],'zcs' => $params['zcs'] ])->count();
            if($count){
                $this->error('该后缀下面已设置了相同的注册商');
            }
            //是否设置默认
            if($params['defu'] == 1){
                $this->model->where(['name' => $params['name'],'defu' => 1])->setField('defu',0);
            }
            $this->model->insert($params);
            $this->success('添加成功');
        }
        
        $suffixs = Db::name('domain_houzhui')->where('zt',1)->order('xh desc')->column('name1');
        $this->view->assign('suffix',$suffixs);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $data = $this->model->find($ids);

        if ($this->request->isPost()){

            $params = $this->request->post('row/a');

            if(  empty($params['ysje'])){
                $this->error('请填写原始金额');
            }
            if($params['yhsj1']){
                $params['yhsj1'] = strtotime($params['yhsj1']);
            }
            if($params['yhsj2']){
                $params['yhsj2'] = strtotime($params['yhsj2']);
            }
            // $count = $this->model->where(['name' => $params['name'],'zcs' => $params['zcs'] ])->where(' zcs != '.$data['zcs'])->count();

            // if($count){
            //     $this->error('该后缀下面已设置了相同的注册商');
            // }

            //是否设置默认
            if($params['defu'] == 1){
                $this->model->where(['name' => $params['name'],'defu' => 1])->setField('defu',0);
            }

            $this->model->update($params);
            $this->success('修改成功');
        }
        $zcs = $this->getApis(-1);
        $data['regname'] = $zcs[$data['aid']]['regname'];
        $data['regid'] = $zcs[$data['aid']]['regid'];
        $apis = Db::name('domain_api')->field('id,tit')->where(['status' => 1,'regid' => $data['zcs']])->select();
        $zcelist = $this->getZcsOption($data['name']);

        $this->view->assign(['data' => $data,'zcelist' => $zcelist['res'],'apis' => $apis]);
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
     * 获取注册商
     */
    public function getZcsOption($hz=""){
        if(empty($hz)){
            $hz = $this->request->param('hz');
        }
        //获取全部注册商
        $zces = $this->getCates();
        //获取已设置的注册商
        $zcslist = $this->model->alias('h')->join('domain_api a','a.id=h.aid')->field('a.regid,h.name')->select();
        foreach($zcslist as $v){
            if(isset($zces[$v['regid']]) && $v['name'] == $hz){
                unset($zces[$v['regid']]);
            }
        }
        if($zces){
            return ['code' => 0, 'msg' => '' , 'res' => $zces];
        }else{
            return ['code' => 1, 'msg' => '无注册商可设置'];
        }
    }

    /**
     * 获取接口商 其他页面
     */
    public function getApisOption(){

        $hz = $this->request->post('hz');

        $data = Db::name('register_suffix_config')->where(['name' => $hz,'zt' => 1])->column('aid');
        $apis = $this->getApis();
        $arr = [];
        foreach($data as $v){
            $arr[] = ['id' => $v,'tit' => $apis[$v]];
        }
        return ['code' => 0, 'msg' => '' , 'res' => $arr];
    }

}
