<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use think\Validate;
/**
 * 后缀设置
 *
 * @icon fa fa-user
 */
class Registrar extends Backend
{


    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {   
        parent::_initialize();
        $this->model = Db::name('domain_zcs');
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
            $total = $this->model->where($where)->where('zt',1)->count();
            $list = $this->model->where($where)->where('zt',1)->order($sort,'asc')->limit($offset, $limit)->select();
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
                //规则验证
                $rule = [
                    'row[name1]'  => 'require',
                    'row[name3]'  => 'require',
                    'row[xh]' => 'require|number',
                ];
                $msg = [
                    'row[name1].require' => '真实姓名必须填写',
                    'row[name3].require' => '自定义名称必须填写',
                    'row[xh].require'    => '排序必须填写',
                    'row[xh].number'   => '请输入合法的数字',
                ];
                $data = [
                    'row[name1]'  => $params['name1'],
                    'row[name3]'  => $params['name3'],
                    'row[xh]'  => $params['xh'],
                ];
                $validate = new Validate($rule, $msg);
                if(!$validate->check($data)){
                    $this->error($validate -> getError());
                }
                $num = $this->model->where(['name1'=>$params['name1']])->whereOr(['name3'=>$params['name3']])->count();
                if($num > 0){
                    $this->error('注册商'.$params['name1'].'已存在');
                }
                $params['name2'] = str_replace(' ','',$params['name1']);
                $params['sj'] = date('Y-m-d H:i:s');
                $this->model->insert($params);
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
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
                //规则验证
                $rule = [
                    'row[name1]'  => 'require',
                    'row[name3]'  => 'require',
                    'row[xh]' => 'require|number',
                ];
                $msg = [
                    'row[name1].require' => '真实姓名必须填写',
                    'row[name3].require' => '自定义名称必须填写',
                    'row[xh].require'    => '排序必须填写',
                    'row[xh].number'   => '请输入合法的数字',
                ];
                $data = [
                    'row[name1]'  => $params['name1'],
                    'row[name3]'  => $params['name3'],
                    'row[xh]'  => $params['xh'],
                ];
                $validate = new Validate($rule, $msg);
                if(!$validate->check($data)){
                    $this->error($validate -> getError());
                }
                //查找 后缀是否唯一
                $num = $this->model->where('id','<>',$params['id'])->where('name1 ="'.$params['name1'].'" or name3="'.$params['name3'].'"')->count();
                if($num > 0){
                    $this->error('注册商'.$params['name1'].'已存在');
                }
                $params['sj'] = date('Y-m-d H:i:s');
                $params['name2'] = str_replace(' ','',$params['name1']);
                $this->model->update($params);
                $this->success('修改成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->field('name1,id,name3,xh,zt')->where(['id'=>$ids])->find();
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this->model -> delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }

}
