<?php

namespace app\admin\controller\activity\pageset;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 精选页面元素设置
 *
 * @icon fa fa-user
 */
class Shiftset extends Backend
{
    
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('discpage_config');
    }
    
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {   
            $cates = $this->getCates('shiftset',true);
            $akey = array_keys($cates);

            $this->delSaleDomain($akey);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->whereIn('type',$akey)->count();
            $list = $this->model->field('id,title,create_time,type,status')->where($where)->whereIn('type',$akey)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();


            $fun = Fun::ini();

            foreach($list as $k=>&$v){
                $v['status'] = $fun->getStatus($v['status'],['--','已启用','已禁用']);
                $v['type'] = isset($cates[$v['type']]) ? $cates[$v['type']] : '--';
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

            // 如果没搜索的话 不能提交
            if(empty($params['title']) ){
                $this->error('请输入域名');
            }

            $tits = Fun::ini()->moreRow($params['title']);
            
            //判断域名是否在出售列表中
            $stits = Db::name('domain_pro_trade')->where(['status' => 1,'lock' => 0])->whereIn('tit',$tits)->column('tit');
            if(count($tits) != count($stits)){
                $smsg = array_diff($tits,$stits);
                $this->error('以下域名暂未出售:'.implode(',',$smsg));
            }

            //查询是否有包含的域名
            $fus = $this->model->where(['type' => $params['type'] ])->whereIn('title',$tits)->column('title');
            if($fus){
                $this->error('该类型中已包括以下域名:'.implode(',', $fus));
            }
            $insert = [];
            $sj = time();
            
            foreach($tits as $v){
                $insert[] = ['title' => $v,'create_time' => $sj,'type' => $params['type'],'status' => $params['status'] ];
            }

            $this->model->insertAll($insert);

            $this->success('添加成功');
        }
        $cates = $this->getCates('shiftset',true);
        $this->assign('cates',$cates);

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL){

        if ($this->request->isPost()){
            $params = $this->request->post('row/a');
            
            // 如果没搜索的话 不能提交
            if( empty($params['title']) ){
                $this->error('请输入域名');
            }

            $tglag = Db::name('domain_pro_trade')->where(['status' => 1,'lock' => 0,'tit' => $params['title'] ])->count();
            if(!$tglag){
                $this->error('以下域名暂未出售:'.$params['title']);
            }
            //查询改类型下是否包含相同的域名
            $flag = $this->model->where(' id !=  '.$params['id'].' and title = "'.$params['title'].'" and  type = '.$params['type'])->count();
            if($flag){
                $this->error('该分类下已包含'.$params['title']);
            }
            $this->model->update($params);

            $this->success('修改成功');
        }
        
        $data = $this->model->field('id,title,type,status')->find($ids);

        $cates = $this->getCates('shiftset',true);

        $this->view->assign(['data' => $data,'cates' => $cates]);
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
     * 删除已出售的域名
     */
    public function delSaleDomain($tids){
        $tit = $this->model->whereIn('type',$tids)->column('title');
        if($tit){
            $saletit = Db::name('domain_pro_trade')->whereIn('tit',$tit)->column('tit');
            $arr = array_diff($tit, $saletit);
            if($arr){
                $this->model->whereIn('type',$tids)->whereIn('title',$arr)->delete();
            }
        }

    }


}
