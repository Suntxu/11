<?php

namespace app\admin\controller\activity\disuffix;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 优惠注册限制活动设置
 *
 * @icon fa fa-user
 */
class Dismate extends Backend
{
    /**
     * @var
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_limit_meal');
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
            $list = $this->model->where($def)->where($where)->order($sort,$order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            foreach($list as &$v){
                $v['title'] = $fun->returntitdian($v['title'],20);
                $v['colony'] = $fun->getStatus($v['colony'],['所有用户','新用户','老用户']);
                $v['type'] = $fun->getStatus($v['type'],['不允许','允许']);
                $v['status'] = $fun->getStatus($v['status'],['停用','启用']);
                $v['show'] = '查看';
                if($sj < $v['end_time']){
                    $v['flag'] = 1;
                    $v['group'] = '未过期';
                }else{
                    $v['flag'] = 0;
                    $v['group'] = '已过期';
                }
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
            $params = $this->request->post("mate/a"); 
            
            if ($params){
                if(empty($params['title']) || empty($params['start_time']) || empty($params['end_time']) ){
                    $this->error('请填写必填参数');
                }
                $params['created_at'] = date('Y-m-d H:i:s');
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);
                if($params['start_time'] > $params['end_time']){
                    $this->error('活动开始时间不能大于活动结束时间');
                }

                $this->limit($params['start_time'],$params['end_time']);

                $this->model->insert($params);
                $this -> success('添加成功');       
            }
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("mate/a"); 
            if ($params){
                if(empty($params['title']) || empty($params['start_time']) || empty($params['end_time']) ){
                    $this->error('请填写必填参数');
                }
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);

                if($params['start_time'] > $params['end_time']){
                    $this->error('活动开始时间不能大于活动结束时间');
                }

                $this->limit($params['start_time'],$params['end_time'],$params['id']);

                $this->model->update($params);
                $this -> success('修改成功');       
            }
        }
        $data = $this->model->find($ids);
        $this->view->assign('data' , $data);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            //查看是否已产生订单记录
            $flag = Db::name('domain_limit_order')->where('lid',$ids)->count();
            if($flag){
                $this->error('该活动已产生订单记录,不能进行删除操作');
            }
            Db::startTrans();
            try{
                $this->model->delete($ids);
                Db::name('domain_limit_houzhui')->where('lid',$ids)->delete();

            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
           
            Db::commit();
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }

    /**
     * 一个时间段只允许一个活动
     */
    private function limit($strttimr,$endtime,$id=null){
        if($id){
            $data = $this->model->field('start_time,end_time')->where('end_time','>',time())->where('id != '.$id)->select();
        }else{
            $data = $this->model->field('start_time,end_time')->where('end_time','>',time())->select();
        }
        foreach($data as $v){
            if(($strttimr > $v['start_time'] && $endtime > $v['start_time'] && $strttimr > $v['end_time'] ) || ($strttimr < $v['start_time'] && $endtime < $v['start_time'] && $strttimr < $v['end_time'] )){
                continue;
            }
            $this->error('此活动的开始时间或结束时间在其他活动的时间段内');
        }
    }

}
