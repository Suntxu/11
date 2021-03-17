<?php

namespace app\admin\controller\advertising;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 广告列表
 *
 * @icon fa fa-user
 */
class Adlist extends Backend
{
    protected $noNeedRight = ['visitlog'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_ad');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            global $remodi_db;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('a')->join('category c','a.cid=c.id')->where($where)->count();
            $list = $this->model->alias('a')->join('category c','a.cid=c.id')
                    ->field('a.tit,a.sm,a.xh,a.zt,a.sj,c.name,a.id,a.dqsj,a.type1,a.dqsj')
                    ->where($where)
                    ->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            $connect = Db::connect($remodi_db)->name('advert_visitlog');
            foreach($list as $k=>$v){
                $list[$k]['zt'] = $fun->getStatus($v['zt'],['--','展示中','队列中']);
                $list[$k]['tit'] = $fun->returntitdian($v['tit'],20);
                $list[$k]['type1'] = $fun->getStatus($v['zt'],['图片','代码','文字','动画']);
                $list[$k]['hits'] = $connect->where('aid',$v['id'])->count();
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('bh',$this->request->get('bh'));
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
                if($params['sj'] == ''){
                    $params['sj'] = time();
                }else{
                    $params['sj'] = strtotime($params['sj']);
                }
                if($params['dqsj'] != ''){
                    $params['dqsj'] = strtotime($params['dqsj']);
                }
                if(!empty($params['path'])){
                    $params['path'] = strstr($params['path'],'?',true);
                }
                $this->model->insert($params);

                if($params['cid'] == 105){ //暂时缓存单个分类
                    $redis = new Redis(['select' => 7]);
                    //获取该分类下面的id
                    $ainfo = $this->model->field('tit,path,aurl,dqsj,id')->where(['type1' => 0,'cid' => $params['cid'],'zt' => 1])->where('dqsj','>',time())->order('xh','asc')->select();
                    
                    $redis->set('ad_list_data_'.$params['cid'],json_encode($ainfo));


                }
                $this->success('添加成功');       
            }
        }
        // 下拉框
        $ruledata = Db::name('category')->field('id,name')->where(['type'=>'ad','pid'=>0,'status'=>'normal'])->select();
        $sel = "<select class='form-control' name=row[cid]>";
        foreach($ruledata as $k => $v){
            $sel.="<option value='{$v['id']}' disabled  >{$v['name']}</option>";
            $arr = Db::name('category')->field('id,name')->where(['status'=>'normal','pid'=>$v['id']])->select();
            foreach($arr as $kk => $vv ){
                $sel.="<option value='{$vv['id']}'>&nbsp;&nbsp;--{$vv['name']}</option>";
            }
        }
        $sel.="</select>";
        $this->view->assign('sel',$sel);   
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
                if($params['sj'] == ''){
                    $params['sj'] = time();
                }else{
                    $params['sj'] = strtotime($params['sj']);
                }
                if($params['dqsj'] != ''){
                    $params['dqsj'] = strtotime($params['dqsj']);
                }
                if(strpos($params['path'],'?')){
                    $params['path'] = strstr($params['path'],'?',true);
                }
                $params['path'] = str_replace('/uploads','',$params['path']);

                $this->model->update($params);  

                if($params['cid'] == 105){ //暂时缓存单个分类
                    $redis = new Redis(['select' => 7]);
                    //获取该分类下面的id
                    $ainfo = $this->model->field('tit,path,aurl,dqsj,id')->where(['cid' => $params['cid'],'zt' => 1])->where('dqsj','>',time())->order('xh','asc')->select();
                    
                    $redis->set('ad_list_data_'.$params['cid'],json_encode($ainfo));

                }

                $this->success('修改成功');       
            }
        }
        
        $data = $this->model->find($ids);
        //分类下拉框
        $ruledata = Db::name('category')->field('id,name')->where(['type'=>'ad','pid'=>0,'status'=>'normal'])->select();
        $sel = "<select class='form-control' name=row[cid]>";
        foreach($ruledata as $k => $v){
            $sel.="<option  value='{$v['id']}' disabled >{$v['name']}</option>";
            $arr = Db::name('category')->field('id,name')->where(['status'=>'normal','pid'=>$v['id']])->select();
            foreach($arr as $kk => $vv ){
                if($data['cid'] == $vv['id']){
                    $sel.="<option selected value='{$vv['id']}'>&nbsp;&nbsp;--{$vv['name']}</option>";
                }else{
                    $sel.="<option value='{$vv['id']}'>&nbsp;&nbsp;--{$vv['name']}</option>";
                }
            }
        }
        $sel.="</select>";

        $this->view->assign(['data'=>$data,'sel'=>$sel]);
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
     * 查看访问记录
     */
    public function visitlog(){

        if ($this->request->isAjax()) {
            global $remodi_db;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::connect($remodi_db)->name('advert_visitlog')->where($where)->count();

            $list = Db::connect($remodi_db)->name('advert_visitlog')
                ->where($where)
                ->order($sort,$order)->limit($offset, $limit)
                ->select();

            $adlist = $this->model->field('id,tit')->select();
            $adlist = array_column($adlist,'tit','id');

            foreach($list as &$v){
                $v['atit'] = empty($adlist[$v['aid']]) ? '-' : $adlist[$v['aid']];
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('id',$this->request->get('ids'));
        return $this->view->fetch();

    }


}
