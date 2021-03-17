<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 提现
 *
 * @icon fa fa-user
 */
class Entrust extends Backend
{
    protected $model = null;

    /**
     * @var \app\admin\model\HZ
     */

    public function _initialize()
    {
        parent::_initialize();
        $this->model= Db::name('domain_entrust');
    }
    /**
     * 查看 
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $def = '';
            if($group){
                $def = ' e.id =  '.substr(trim($group),6);
            }
            $total = $this->model->alias('e')->join('domain_user u','e.userid=u.id')
                        ->where($where)->where($def)
                        ->count();

            $list = $this->model->alias('e')->join('domain_user u','e.userid=u.id')
                        ->field('e.tit,e.money,e.qq,e.mot,e.email,e.status,e.create_time,e.id,u.uid')
                        ->where($where)->where($def)->order($sort,$order)->limit($offset, $limit)
                        ->select();
            $fun = Fun::ini();
            
            foreach($list as &$v){
                $v['e.status'] = $fun->getStatus($v['status'],['<font color="red">待受理</font>','<font color="#c88400">已受理</font>','<font color="green">成功</font>','<font color="red">失败</font>']); 
                $v['e.money'] = $v['money'];
                $v['e.create_time'] = $v['create_time'];
                $v['group'] = 'WT0081'.$v['id'];
                $v['e.tit'] = $fun->returntitdian($v['tit'],15);
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    // 详情
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){

            $params = $this->request->post("row/a");

            if ($params){
                if(empty($params['status'])){
                    $this->error('缺少审核参数');                    
                }
                $this->model->update($params);
                $this->success('修改成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->alias('e')->join('domain_user u','e.userid=u.id')
            ->field('e.tit,e.money,e.remark,e.txt,e.status,e.id,e.create_time,u.uid')
            ->find($ids);
        $data['zt'] = Fun::ini()->getStatus($data['status'],['<font color="red">待受理</font>','<font color="#c88400">已受理</font>','<font color="green">成功</font>','<font color="red">失败</font>']);
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
}
