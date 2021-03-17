<?php

namespace app\admin\controller\activity\welfare;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名注册包套餐管理
 * @icon fa fa-user
 */
class Combo extends Backend
{
    /**
     * @var
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_welfare_meal');
    }
    
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = $this->model->alias('m')->join('domain_welfare w','m.wid=w.id')
                    ->where($where)
                    ->count();
            
            $field = '(select sum(success_num) from '.PREFIX.'domain_welfare_order where hd_id=m.id) as snum,(select sum(reging_num) from '.PREFIX.'domain_welfare_order where hd_id=m.id) as regnum';

            $list = $this->model->alias('m')->join('domain_welfare w','m.wid=w.id')
                    ->field('m.id,m.title,m.domain_total,m.pack_total,m.surplus_total,m.discount_cost,m.status,m.start_time,m.end_time,m.create_time,m.suffix,w.title as wtitle,w.original_cost,m.sort,'.$field)
                    ->where($where)->order($sort,$order)
                    ->limit($offset, $limit)
                    ->select();
            
            $sinfo = $this->model->alias('m')->join('domain_welfare w','m.wid=w.id')->join('domain_welfare_order o','o.hd_id=m.id','left')
                    ->field('sum(o.success_num) as snum,sum(o.reging_num) as regnum')
                    ->where($where)
                    ->find();

            $fun = Fun::ini();

            foreach($list as &$v){

                if(mb_strlen($v['title']) > 15){
                    $v['m.title'] = $fun->returntitdian($v['title'],15).' <span onclick="showRemark(\''.$v['title'].'\')" style="color:#52a8f1;cursor: pointer;">查看</span>';;                    
                }else{
                    $v['m.title'] = $v['title'];
                }
                $v['m.start_time'] = $v['start_time'];
                $v['m.end_time'] = $v['end_time'];

                if(mb_strlen($v['wtitle']) > 15){
                    $v['w.title'] = $fun->returntitdian($v['wtitle'],15).' <span onclick="showRemark(\''.$v['wtitle'].'\')" style="color:#52a8f1;cursor: pointer;">查看</span>';;                    
                }else{
                    $v['w.title'] = $v['wtitle'];
                }
                $v['m.sort'] = $v['sort'];
                $v['m.create_time'] = $v['create_time'];

                $v['m.status'] = $fun->getStatus($v['status'],['开启','关闭']);

                $v['m.suffix'] = $v['suffix'];
                $v['zregnum'] = $sinfo['regnum'];
                $v['zsnum'] = $sinfo['snum'];

                
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
                if(empty($params['title']) || empty($params['wid']) || empty($params['domain_total']) || empty($params['discount_cost']) || empty($params['start_time']) || empty($params['end_time'])){
                    $this->error('请填写必填参数');
                }

                $params['create_time'] = time();
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);
                if($params['start_time'] > $params['end_time']){
                    $this->error('注册开始时间不能大于注册结束时间');
                }
                $params['surplus_total'] = $params['pack_total'];

                $this->model->insert($params);
                $this -> success('添加成功');       
            }
        }

        $this->view->assign('activiInfo',$this->getActiviInfo());

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

                if(empty($params['title']) || empty($params['wid']) || empty($params['start_time']) || empty($params['end_time'])){
                    $this->error('请填写必填参数');
                }

                if($params['status'] === 0){
                    if(empty($params['domain_total']) || empty($params['discount_cost'])){
                        $this->error('请填写必填参数');
                    }
                }

                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);

                if($params['start_time'] > $params['end_time']){
                    $this->error('注册开始时间不能大于注册结束时间');
                }
                
                
                //获取之前的包数量
                $packTotal = $this->model->field('pack_total,surplus_total')->where('id',$params['id'])->find();

                //获取已购买的数量
                $orderTotal = Db::name('domain_welfare_order')->where('hd_id',$params['id'])->count();

                if($orderTotal > $params['pack_total']){

                    $this->error('该套餐设置的包数量不得少于剩余注册数量');
                
                }
                $params['surplus_total'] = $packTotal['surplus_total'] + ($params['pack_total'] - $packTotal['pack_total']);


                $this->model->update($params);

                $this -> success('修改成功');       
            }
        }
        $data = $this->model->find($ids);
        $this->view->assign('data' , $data);
        $this->view->assign('activiInfo',$this->getActiviInfo());
        return $this->view->fetch();
    }

    /**
     * 删除功能
     */
    public function del($ids=null){
        if($ids){
            
            $this->model->delete($ids);
            $this->success('删除成功');
        }
    }

    /**
     * 获取后缀信息
     */
    private function getActiviInfo(){

        $info = Db::name('domain_welfare')->field('id,suffix,title,start_time,end_time')->where('status',0)->select();

        return $info;

    }

    /**
     * 检测有效天数
     */
    private function limit($wid,$indate){
       
        $endTime = Db::name('domain_welfare')->where(['id' => $wid,'status' => 0])->value('end_time');
        
        if(empty($endTime)){
            $this->error('选择的福利活动不存在');
        }

        if($endTime < strtotime('+ '.$indate.'day')){
            $this->error('有效期错误,请填写有效期结束后小于'.date('Y-m-d H:i:s',$endTime).'的天数');
        }

    }

}
