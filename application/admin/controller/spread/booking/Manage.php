<?php

namespace app\admin\controller\spread\booking;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
//拼团列表控制器
class Manage extends Backend
{
    protected $relationSearch = false;
    protected $fun = null;
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('assemble_team');
    }
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $what = $this->model->alias('a')->join('domain_user b','a.uid=b.id','left')
                ->field('a.*,b.uid')
                ->where($where)->order($sort,$order)->limit($offset, $limit)
                ->select();
            
            $total = $this->model->alias('a')->join('domain_user b','a.uid=b.id','left')
                ->field('a.*,b.uid')
                ->where($where)
                ->count();

            $fun = Fun::ini();
            foreach ($what as $k=>$v)
            {
                if ($what[$k]['status'] ==  -1)
                {
                    $what[$k]['status'] =  '审核失败';
                }else{
                    $what[$k]['status'] =   $fun->getStatus($v['status'],['待审核','组队中','认领完成','认领失败','部分完成','完成']);
                }
            }
            $result = array("total" => $total,"rows" => $what);
            return  json($result);
        }
        return $this->view->fetch();
    }
    public function edit($ids=''){
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if (!empty($params))
            {
                  if ($params['status'] ==  -1)
                  {
                      if (empty($params['remark']))
                      {
                          return  $this->error('审核失败必填备注');
                      }
                  }
                  else
                  {
                          $params['start_at']   =   time();
                          $shichang =   Db::name('assemble_meal')->where('id',$params['mid'])->value('sid');
                          $ttm  =   Db::name("assemble_suffix")->where('id',$shichang)->value('dur_active');
                          $params['end_at'] =   $params['start_at']+($ttm*86400);
                  }
                  $status   =   Db::name('assemble_team')->where('id',$ids)->value('status');
                  if ($status   ==  0)
                  {
                    $iswhat =   Db::name('assemble_team')->where('id',$ids)->update($params);
                        if (isset($iswhat))
                        {
                            $tid    =   $ids;
                            if ($params['status'] >  -1)
                            {
                                $log    =   "审核通过";
                            }
                            else
                            {
                                $log    =   "审核失败,备注".$params['remark'];
                            }
                            $type   =   0;
                            $admin  =   session('admin');
                            $admin_id   =   $admin['id'];
                            $admin_name   =   $admin['username'];
                            $this->booking_log($tid,$log,$type,$admin_id,$admin_name);
                            return  $this->success('修改成功');
                        }
                        else
                        {
                            return  $this->error('修改失败');
                        }
                  }
                  else
                  {
                      return $this->error("系统错误");
                  }
            }
            else
            {
                return    $this->error("状态错误");
            }
        }
        $list   =$this->model->alias('a')->join('domain_user b','a.uid=b.id','left')
                ->field('a.*,b.uid as name')
                ->where('a.id',$ids)
                ->find();
        $this->assign('data',$list);
        return  $this->view->fetch();
    }
    public function del($ids    =   '')
    {
        $id['id']   =   $ids;
        $list   =   Db::name('assemble_meal')->delete($id);
        if (isset($list))
        {
            return  $this->success('删除成功');
        }
        else{
            return  $this->error("删除失败");
        }
    }
}
