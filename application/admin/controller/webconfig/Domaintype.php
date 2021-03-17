<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use think\Validate;
use fast\Tree;

/**
 * 后缀设置
 *
 * @icon fa fa-user
 */
class Domaintype extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_fenlei');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {   
            // list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $tree = Tree::instance();
            $tree->init(collection($this->model->order('xh asc,id desc')->select())->toArray(), 'pid');
            $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name1');
            $categorydata = [0 => ['type' => 'all', 'name' => __('None')]];
            foreach ($this->categorylist as $k => $v)
            {
                $categorydata[$v['id']] = $v;
            }
            array_shift($categorydata);
            foreach($categorydata as $k => &$v){
                if($v['zt'] == 1){
                    $v['zt'] = '正常';
                }else{
                    $v['zt'] = '关闭';
                }
                if(empty($v['name2'])){
                    $v['name'] = $v['name1'];
                }else{
                    $v['name'] = $v['spacer'].$v['name2'];
                }
            }
            $result = array("total" => count($categorydata), "rows" => $categorydata);
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
                    'row[name]'  => 'require',
                    'row[sbid]'   => 'require|number',
                    'row[xh]' => 'require|number',
                ];
                $msg = [
                    'row[name].require' => '分类必须填写',
                    'row[sbid].require'    => '识别ID必须填写',
                    'row[sbid].number'   => '请输入合法的数字',
                    'row[xh].require'    => '排序必须填写',
                    'row[xh].number'   => '请输入合法的数字',
                ];
                $data = [
                    'row[name]'  => $params['name'],
                    'row[xh]'  => $params['xh'],
                    'row[sbid]'  => $params['sbid'],
                ];
                $validate = new Validate($rule, $msg);
                if(!$validate->check($data)){
                    $this->error($validate -> getError());
                }
                //查找 后缀是否唯一
                if(empty($params['pid'])){
                    $num = $this -> model -> where(['name1'=>$params['name']]) -> count();
                    $params['name1'] = $params['name'];
                    $params['admin'] = 1;
                    $params['pid'] = 0;
                }else{
                    $num = $this -> model -> where(['name2'=>$params['name']]) -> count();
                    $arr = explode('||',$params['pid']);
                    $params['name1'] = $arr[1];
                    $params['pid'] = $arr[0];
                    $params['admin'] = 2;
                    $params['name2'] = $params['name'];
                }
                if($num > 0){
                    $this -> error('分类'.$params['name'].'已存在');
                }
                //判断识别ID
                $num1 = $this->model->where('sbid',$params['sbid']) -> count();
                if($num1 > 0){
                    $this -> error('识别ID'.$params['sbid'].'已存在');
                }
                unset($params['name']);
                $params['sj'] = date('Y-m-d H:i:s');
                $this->model->insert($params);
                $this -> success('添加成功');
            }else{
                $this -> error('缺少数据');
            }
        }
        //获取父级分类
        $list = $this->model->field('id,name1')->where(['pid'=>0,'zt'=>1])->order('xh') -> select();
        $this->view->assign('pdata',$list);
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
                    'row[name]'  => 'require',
                    'row[sbid]'   => 'require|number',
                    'row[xh]' => 'require|number',
                ];
                $msg = [
                    'row[name].require' => '分类必须填写',
                    'row[sbid].require'    => '识别ID必须填写',
                    'row[sbid].number'   => '请输入合法的数字',
                    'row[xh].require'    => '排序必须填写',
                    'row[xh].number'   => '请输入合法的数字',
                ];
                $data = [
                    'row[name]'  => $params['name'],
                    'row[xh]'  => $params['xh'],
                    'row[sbid]'  => $params['sbid'],
                ];
                $validate = new Validate($rule, $msg);
                if(!$validate->check($data)){
                    $this->error($validate -> getError());
                }
                //查找 后缀是否唯一
                if(empty($params['pid'])){
                    $params['name1'] = $params['name'];
                    //修改所有的父ID
                    $this->model->where(['pid'=>$params['id']])->update(['name1'=>$params['name1']]);
                    $num = $this->model->where(['name1'=>$params['name']]) -> where('id&pid','<>',$params['id'])->count();
                }else{
                    $params['name2'] = $params['name'];
                    $num = $this->model->where(['name2'=>$params['name']])->where('id&pid','<>',$params['id'])->count();
                }
                if($num > 0){
                    $this -> error('分类'.$params['name'].'已存在');
                }
                unset($params['name']);
                $params['sj'] = date('Y-m-d H:i:s');
                $this->model->update($params);
                $this->success('添加成功');
            }else{
                $this -> error('缺少数据');
            }
        }
        $data = $this->model->field('id,name2,name1,sbid,xh,pid,zt')->find($ids);
        $this->assign('data',$data);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            //关联删除
            $data = $this->model->field('pid')->where(['id'=>$ids])->find();
            if(empty($data['pid'])){
                //父级分类 关联删除
                $this->model->where('pid','=',$ids)->delete();
            }
            $this->model->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }

}
