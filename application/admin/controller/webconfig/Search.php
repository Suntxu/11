<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 热门搜索o设置
 *
 * @icon fa fa-user
 */
class Search extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('top_search');
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
            $list = $this->model->field('id,condition,name,status,seotit,seokey,seodesc,type,create_time')
                ->where($where)->order('djl','desc')
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            foreach($list as $k=>&$v){
                $v['status'] = Fun::ini()->getStatus($v['status'],['--','已启用','已禁用']);
                $v['type'] = Fun::ini()->getStatus($v['type'],['一口价页面','快捷搜索(分类)']);
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
            $data = $this->request->post();
            $params = array_filter($data['row']);
            if ($params){
                // 如果没搜索的话 不能提交
                if(empty($data['name'])){
                    $this->error('请输入名称');
                }
                $params['status'] = $data['status']; //上架域名
                $params['type'] = 1; //一口价域名
                $condition = json_encode($params);
                $seo = $data['seo'];
                $this->model->insert([
                    'condition' => $condition,
                    'name' => $data['name'],
                    'create_time' => time(),
                    'status' => $data['status'],
                    'seotit' => $seo['seotit'],
                    'seokey' => $seo['seokey'],
                    'seodesc' => $seo['seodesc'],
                    'type'  => $data['type'],
                ]);
                $this->success('添加成功');
            }else{
                $this->error('缺少搜索条件');
            }
        }
        // 获取域名分类
        $domainType = Fun::ini()->getDomainType();
        $option = '';
        foreach($domainType as $k => $v){
                $option .= '<option value="'.$k.'"> -- '.$v[0].'</option>';
                foreach($v[1] as $kk => $vv){
                    $option .= '<option value="'.$kk.'"> ---- '.$vv.'</option>';
                }
        }
        //获取域名后缀
        $hz = Db::name('domain_houzhui')->where(['zt'=>1])->order('xh asc')->column('name1');
        $this->view->assign([
            'hz' => $hz,
            'option' => $option,
        ]);
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){
            $data = $this->request->post();
            $params = array_filter($data['row']);
            if ($params){
                // 如果没搜索的话 不能提交
                if(empty($data['name'])){
                    $this->error('请输入名称');
                }
                $params['status'] = $data['status']; //上架域名
                $params['type'] = 1; //一口价域名
                $condition = json_encode($params);
                $seo = $data['seo'];
                $this->model->update([
                    'condition' => $condition,
                    'name' => $data['name'],
                    'status' => $data['status'],
                    'id' => $data['id'],
                    'seotit' => $seo['seotit'],
                    'seokey' => $seo['seokey'],
                    'seodesc' => $seo['seodesc'],
                    'type' => $data['type'],
                ]);
                $this->success('修改成功');
            }else{
                $this->error('缺少搜索条件');
            }
        }
        $data = $this->model->field('id,condition,name,status,seotit,seokey,seodesc,type')->find($ids);
        $condition = json_decode($data['condition'],true);
        if(empty($condition['dtype'])){
            $tyidv = '0'; //为空防止报错strstr
        }else{
            $tyidv = $condition['dtype'];
        }
        // 获取域名分类
        $domainType = Fun::ini()->getDomainType();
        $option = '';
        foreach($domainType as $k => $v){
            $flag = ($k == $tyidv) ? 'selected':'';
            $option .= '<option value="'.$k.'" '.$flag.'  > -- '.$v[0].'</option>';
            foreach($v[1] as $kk => $vv){
                $flag = ($kk == $tyidv) ? 'selected':'';
                $option .= '<option value="'.$kk.'" '.$flag.' > ---- '.$vv.'</option>';
            }
        }
        //获取域名后缀
        $hz = Db::name('domain_houzhui')->where(['zt'=>1])->order('xh asc')->column('name1');
        $this->view->assign([
            'hz' => $hz,
            'option' => $option,
            'data' => $data,
            'condition' => $condition,
        ]);
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
}
