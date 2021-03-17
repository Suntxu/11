<?php

namespace app\admin\controller\news;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use fast\Tree;
/**
 * 帮助中心
 *
 * @icon fa fa-user
 */
class Help extends Backend
{

    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_help');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('h')->join('domain_helptype p','h.ty1id=p.id')->where($where)->count();
            $list = $this->model->alias('h')->join('domain_helptype p','h.ty1id=p.id')->field('h.tit,h.zt,h.djl,h.sj,h.id,p.name1')->where($where)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
               $list[$k]['h.zt'] = $fun->getStatus($v['zt'],['----','通过审核','正在审核','审核被拒']);
               $list[$k]['tit'] = $fun->returntitdian($v['tit'],40);
               $list[$k]['h.sj'] = $v['sj'];
               $list[$k]['alink'] = SPREAD_URL;
               $list[$k]['ty1id'] = $v['name1'];
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
                if(empty($params['ty1id'])){
                    $this->error('请选择分组');
                }
                if(empty($params['tit'])){
                    $this->error('标题必须填写！');
                }
                if($params['wdes'] == ''){
                    $params['wdes'] =  mb_substr($params['txt'],0,220,'utf-8');
                }
                if($params['sj'] == ''){
                    $params['sj'] = date('Y-m-d H:i:s');
                }
               
                $params['bh'] = time().'h'.rand(10,99);
                $this->model->insert($params);
                $this->success('添加成功');  
            }
        }
        $this->view->assign('sel',$this->getSelect());
        return $this->view->fetch();
    }

     /**
     * 编辑
     */
    public function edit($ids='')
    {

        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['tit'])){
                    $this->error('标题必须填写！');
                }
                if($params['wdes'] == ''){
                    $params['wdes'] =  mb_substr($params['txt'],0,220,'utf-8');
                }
                if($params['sj'] == ''){
                    $params['sj'] = date('Y-m-d H:i:s');
                }
                $this->model->update($params);    
                $this->success('修改成功');       
            }
        }
        $data = $this->model->field('id,tit,wkey,wdes,txt,sj,djl,orderby,zt,ty1id')->find($ids);

        $this->view->assign(['sel' => $this->getSelect($data['ty1id']),'data'=> $data ]);
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
     * 获取下拉框
     * @param sid 选中的项id
     */
    private function getSelect($sid = null){

        $cate = Tree::instance();
        $pids = Db::name('domain_helptype')->where(['pid' => 0,'zt' => 1])->column('id');
        $arr = Db::name('domain_helptype')->field('id,name1 as name,pid')->where('zt',1)->select();
        $tree = $cate->init($arr)->getTree(0,'<option value=@id @selected @disabled>@spacer@name</option>',$sid,$pids);
        return $tree;
    }
}
