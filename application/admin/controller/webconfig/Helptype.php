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
class Helptype extends Backend
{

    protected $noNeedRight = ['getTypeList'];
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_helptype');
    }

    /**
     * 查看
     */
    public function index()
    {
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
                $v['name'] = $v['name1'];
                if($v['zt'] == 1){
                    $v['zt'] = '正常';
                }else{
                    $v['zt'] = '关闭';
                }
                $categorydata[$k]['nr'] = Db::name('domain_help')->where('ty1id='.$v['id'].' and zt != 99 ')->count();
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
                    'row[name1]'  => 'require',
                    'row[xh]' => 'require|number',
                ];
                $msg = [
                    'row[name1].require' => '分类必须填写',
                    'row[xh].require'    => '排序必须填写',
                    'row[xh].number'   => '请输入合法的数字',
                ];
                $data = [
                    'row[name1]'  => $params['name1'],
                    'row[xh]'  => $params['xh'],
                ];
                $validate = new Validate($rule, $msg);
                if(!$validate->check($data)){
                    $this->error($validate -> getError());
                }
                $num = $this->model->where(['name1'=>$params['name1']])->count();
                if($num > 0){
                    $this->error('分类'.$params['name1'].'已存在');
                }
                
                $params['sj'] = date('Y-m-d H:i:s');
                $this->model->insert($params);
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
         //获取父级分类
        $list = $this->model->field('id,name1')->where(['pid'=>0,'zt'=>1])->order('xh')->select();
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
                    'row[xh]' => 'require|number',
                ];
                $msg = [
                    'row[xh].require'    => '排序必须填写',
                    'row[xh].number'   => '请输入合法的数字',
                ];
                $data = [
                    'row[xh]'  => $params['xh'],
                ];
                $validate = new Validate($rule, $msg);
                if(!$validate->check($data)){
                    $this->error($validate -> getError());
                }
                $params['sj'] = date('Y-m-d H:i:s');
                $this->model->where('id',$params['id'])->update($params);
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->field('id,name1,aurl,xh,zt,pid')->where(['id'=>$ids])->find();
       
        $this->view->assign('data',$data);
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
                // 删除帮助中心
                $sid = $this->model->where(['pid'=>$ids])->column('id');
                Db::name('domain_help')->whereIn('ty1id',$sid)->delete();
                //父级分类 关联删除
                $this->model->where('pid','=',$ids)->delete();
            }
            Db::name('domain_help')->where(['ty1id'=>$ids])->delete();
            $this->model->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }

    /**
     * 获取分类列表
     */
    public function getTypeList(){
        
       $data = $this->model->where('zt = 1 and pid != 0 ')->field('id,name1 as name')->select();
       return $data;
    }

}
