<?php

namespace app\admin\controller\spread\booking;

use app\common\controller\Backend;
use think\Db;
use think\Validate;
use app\admin\common\Fun;
//拼团优惠列表控制器
class Voucher extends Backend
{
    protected $relationSearch = false;
    protected $fun = null;
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('assemble_meal');
    }
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $what = $this->model->alias('a')->join('assemble_suffix b','a.sid=b.id','left')
                ->field('a.*,b.suffix')
                ->where($where)->order($sort,$order)->limit($offset, $limit)
                ->select();
            $total  = $this->model->alias('a')->join('assemble_suffix b','a.sid=b.id','left')
                ->where($where)
                ->count();
            $apis = $this->getApis();
            foreach ($what as $k=>$v)
            {
                if ($what[$k]['status'] ==  0)
                {
                    $what[$k]['status'] =  "禁用";
                }else
                {
                    $what[$k]['status'] =  "正常";
                }
                // $what[$k]['aid'] = empty($v['aid']) ? '--' : $apis[$v['aid']];

            }
            $result = array("total" => $total,"rows" => $what);
            return  json($result);
        }
        return $this->view->fetch();
    }
    public function edit($ids=''){
        if ($this->request->isPost())
        {
            $row = $this->request->post("row/a");
            $iswhat =   Db::name('assemble_meal')->where('id',$row['id'])->update($row);
            if (isset($iswhat))
            {
                $tid    =   0;
                $log    =   "修改套餐".$row['title'];
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
        };
        $list   = $this->model->alias('a')->join('assemble_suffix b','a.sid=b.id','left')
                ->field('a.*,b.suffix')
                ->where('a.id',$ids)
                ->find();

        $apis = $this->getApis();
        // $list['aid'] = empty($list['aid']) ? '--' : $apis[$list['aid']];

        $this->assign('data',$list);
        return  $this->view->fetch();
    }
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            $list   =   Db::name('assemble_meal')->insert($params);
            if (isset($list))
            {
                $tid    =   0;
                $log    =   "添加套餐".$params['title'];
                $type   =   0;
                $admin  =   session('admin');
                $admin_id   =   $admin['id'];
                $admin_name   =   $admin['username'];
                $this->booking_log($tid,$log,$type,$admin_id,$admin_name);
                return  $this->success("添加成功");
            }else{
                return  $this->error("添加失败");
            }
        }
        $lists   =   Db::name('assemble_suffix')->field('id,suffix,hd_title')->select();
        $apis = $this->getApis();
        $this->assign(['list' => $lists,'apis' => $apis]);
        return  $this->view->fetch();
    }
    public function del($ids    =   '')
    {
        $array  =   explode(',',$ids);
        foreach($array as $key=>$value)
        {
            $list   =   Db::name('assemble_meal')->delete(['id'=>$value]);
        }
        if (isset($list))
        {
            $tid    =   0;
            $log    =   "删除套餐id为".$ids;
            $type   =   0;
            $admin  =   session('admin');
            $admin_id   =   $admin['id'];
            $admin_name   =   $admin['username'];
            $this->booking_log($tid,$log,$type,$admin_id,$admin_name);
            return  $this->success('删除成功');
        }
        else{
            return  $this->error("删除失败");
        }
    }
}
