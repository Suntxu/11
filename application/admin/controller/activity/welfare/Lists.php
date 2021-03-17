<?php

namespace app\admin\controller\activity\welfare;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名注册包管理
 * @icon fa fa-user
 */
class Lists extends Backend
{
    /**
     * @var
     */
    protected $model = null;

    public function _initialize()
    {   
        parent::_initialize();
        $this->model = Db::name('domain_welfare');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $sj = time();
            if($group == 1){
                $def = 'end_time > '.$sj;
            }elseif($group == 2){   
                $def = 'end_time < '.$sj;
            }else{
                $def = '';
            }

            $total = $this->model->where($def)->where($where)->count();
            $list = $this->model->field('id,title,create_time,start_time,end_time,status,suffix,original_cost,sort,api_id,cost')
                    ->where($def)->where($where)->order($sort,$order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            $apiInfo = $this->getApis();
            
            foreach($list as &$v){
                $v['atitle'] = $v['title'];

                $v['title'] = '<a class="dialogit"  title="福利管理" href="/admin/activity/welfare/combo?w.title='.$v['title'].'">'.$v['title'].'</a>';

                if(mb_strlen($v['atitle']) > 15){

                    $v['title'] = '<a class="dialogit"  title="福利管理" href="/admin/activity/welfare/combo?w.title='.$v['atitle'].'">'.$fun->returntitdian($v['atitle'],15).'</a> <span onclick="showRemark(\''.$v['atitle'].'\')" style="color:#52a8f1;cursor: pointer;">查看</span>';
                
                }


                $v['status'] = $fun->getStatus($v['status'],['开启','关闭']);

                if($sj < $v['end_time']){
                    $v['flag'] = 1;
                    $v['group'] = '未过期';
                }else{
                    $v['flag'] = 0;
                    $v['group'] = '已过期';
                }
                $v['api_id'] = isset($apiInfo[$v['api_id']]) ? $apiInfo[$v['api_id']] : '--';
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
                if(empty($params['title']) || empty($params['start_time']) || empty($params['end_time']) || empty($params['suffix']) || empty($params['original_cost']) || empty($params['api_id']) || empty($params['cost']) ){
                    $this->error('请填写必填参数');
                }

                $params['create_time'] = time();
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);
                if($params['start_time'] > $params['end_time']){
                    $this->error('福利包开始时间不能大于福利包结束时间');
                }

                // $this->limit($params['start_time'],$params['end_time'],$params['suffix']);

                $this->model->insert($params);
                $this -> success('添加成功');       
            }
        }

        $this->view->assign([
            'suffixInfo' => $this->getSuffixInfo(),
            'apiInfo' => $this->getApis(),
        ]);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a"); 
            if ($params){

                if(empty($params['title']) || empty($params['start_time']) || empty($params['end_time']) || empty($params['suffix']) || empty($params['original_cost']) || empty($params['cost'])){
                    $this->error('请填写必填参数');
                }
                
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);

                if($params['start_time'] > $params['end_time']){
                    $this->error('福利包开始时间不能大于福利包结束时间');
                }

                // $this->limit($params['start_time'],$params['end_time'],$params['suffix'],$params['id']);
                
                $this->model->update($params);

                $this -> success('修改成功');       
            }
        }
        $data = $this->model->find($ids);

        $this->view->assign([
            'data' => $data,
            'suffixInfo' => $this->getSuffixInfo(),
            'apiInfo' => $this->getApis(),
        ]);
        return $this->view->fetch();
    }


    /**
     * 删除功能
     */
    public function del($ids=null){
        if($ids){
            $flag = Db::name('domain_welfare_meal')->where('wid',$ids)->count();
            if($flag){
                $this->error('请删除该福利下的套餐后,再删除该福利！');
            }
            $this->model->delete($ids);
            $this->success('删除成功');
        }
    }


    /**
     * 获取后缀信息
     */
    private function getSuffixInfo(){


        $info = Db::name('domain_houzhui')->field('ysje,name1,cost')->where('zt',1)->order('xh','desc')->select();

        return $info;


    }



    /**
     * 一个时间段只允许一个活动
     */
    private function limit($strttimr,$endtime,$suffix,$id=null){
        if($id){
            $data = $this->model->field('start_time,end_time,suffix')->where('end_time','>',time())->where('id != '.$id)->where('status',0)->select();
        }else{
            $data = $this->model->field('start_time,end_time,suffix')->where('end_time','>',time())->where('status',0)->select();
        }
        foreach($data as $v){
            if($suffix == $v['suffix']){
                if(($strttimr > $v['start_time'] && $endtime > $v['start_time'] && $strttimr > $v['end_time'] ) || ($strttimr < $v['start_time'] && $endtime < $v['start_time'] && $strttimr < $v['end_time'] )){
                    continue;
                }
                $this->error('此活动后缀的开始时间或结束时间在其他活动的时间段内');

            }
            
        }
    }

}
