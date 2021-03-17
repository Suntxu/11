<?php

namespace app\admin\controller\news;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use fast\Tree;
/**
 * 资讯列表
 *
 * @icon fa fa-user
 */
class Newz extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_news');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('n')->join('domain_newstype p','n.type1id=p.id')->where($where)->count();
            $list = $this->model->alias('n')->join('domain_newstype p','n.type1id=p.id')
                    ->field('n.id,n.tit,n.zt,n.djl,n.sj,p.name1')
                    ->where($where)
                    ->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k=>$v){
               $list[$k]['n.zt'] = $fun->getStatus($v['zt'],['----','通过审核','正在审核','审核被拒']);
               $list[$k]['tit'] = $fun->returntitdian($v['tit'],40);
               $list[$k]['n.sj'] = $v['sj'];
               $list[$k]['alink'] = SPREAD_URL;
               $list[$k]['type1id'] = $v['name1'];
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
                if(empty($params['tit'])){
                    $this->error('标题必须填写！');
                }
                if(empty($params['txt'])){
                    $this->error('请填写内容');
                }
                if(empty($params['type1id'])){
                    $this->error('请选择分组');
                }
                if($params['wdes'] == ''){
                    $params['wdes'] =  mb_substr($params['txt'],0,220,'utf-8');
                }
                if(empty($params['imgpath'])){ //抓取内容的第一张主图
                    $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
                    preg_match_all($preg, $params['txt'], $allImg);
                    if(isset($allImg[1][0])){
                        $params['imgpath'] = $allImg[1][0];
                    }
                }else{
                    $params['imgpath'] = 'http://imgcdn.huaimi.com'.strstr($params['imgpath'],'?',true);
                }
                if(empty($params['sj'])){
                    $params['sj'] = time();
                }else{
                    $params['sj'] = strtotime($params['sj']);
                }
                $this->model->insert($params);
                $this->success('添加成功');       
            }
        }
        $this->view->assign('sel',$this->getSelect());
        return $this->view->fetch();
    }
     /**
     * 添加
     */
    public function edit($ids='')
    {

        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['tit'])){
                    $this->error('标题必须填写！');
                }
                if(empty($params['txt'])){
                    $this->error('请填写内容');
                }
                if($params['wdes'] == ''){
                    $params['wdes'] =  mb_substr($params['txt'],0,220,'utf-8');
                }
                if(empty($params['imgpath'])){
                    $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
                    preg_match_all($preg, $params['txt'], $allImg);
                    if(isset($allImg[1][0])){
                        $params['imgpath'] = $allImg[1][0];
                    }
                }else{
                    if(!strpos($params['imgpath'],'imgcdn')){
                        $params['imgpath'] = 'http://imgcdn.huaimi.com'.strstr($params['imgpath'],'?',true);
                    }
                }

               
                if(!empty($params['sj'])){
                   $params['sj'] = strtotime($params['sj']);
                }
                $this->model->update($params);
                $this->success('修改成功');        
            }
        }
        
        $data = $this->model->field('id,ifjc,tit,titys,zze,ly,wkey,wdes,txt,djl,zt,type1id,imgpath,sj')->find($ids);
        $this->view->assign(['sel' => $this->getSelect($data['type1id']), 'data' => $data ]);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this ->model->delete($ids);
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
        $pids = Db::name('domain_newstype')->where(['pid' => 0,'zt' => 1])->column('id');
        $arr = Db::name('domain_newstype')->field('id,name1 as name,pid')->where('zt',1)->select();
        $tree = $cate->init($arr)->getTree(0,'<option value=@id @selected @disabled>@spacer@name</option>',$sid,$pids);
        return $tree;
    }

}
