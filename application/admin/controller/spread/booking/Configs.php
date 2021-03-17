<?php

namespace app\admin\controller\spread\booking;

use app\common\controller\Backend;
use think\Db;
//拼团域名控制器
class Configs extends Backend
{

    protected $relationSearch = false;
    protected $fun = null;
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('assemble_suffix');
    }
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list   =    Db::name('assemble_suffix')->order($sort, $order)->where($where)->limit($offset, $limit)->select();
            $total  =  Db::name('assemble_suffix')->order($sort, $order)->where($where)->count();
            $time   =   time();
            $apis = $this->getApis();
            foreach($list as $k=>$v)
            {
                if ($time>$v['reg_at'])
                {
                    $list[$k]['ztai']   =   "注册中";
                }
                if ($time>$v['start_at'])
                {
                    $list[$k]['ztai']   =   "进行中";
                }
                if ($time>$v['end_at'])
                {
                    $list[$k]['ztai']   =   "已结束";
                }
                if ($time<$v['reg_at'])
                {
                    $list[$k]['ztai']   =   "未开始";
                }
                // $list[$k]['aid'] = empty($v['aid']) ? '--' : $apis[$v['aid']];

            }
            $result = array("total" => $total,"rows" => $list);
            return  json($result);
        }
        return $this->view->fetch();
    }
    
    public function edit($ids=''){
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            $update['hd_title'] =   $params['hd_title'];
            $update['dur_active'] =   $params['dur_active'];
            $update['start_at']   =   strtotime($params['start_at']);
            $update['end_at']   =   strtotime($params['end_at']);
            $update['reg_at']   =   strtotime($params['reg_at']);

            $iswhat=Db::name('assemble_suffix')->where('id',$ids)->update($update);
            if (isset($iswhat))
            {
                for ($i=0;$i<3;$i++)
                {
                    if (isset($params['id'.$i]))
                    {
                        $sss[$i]['id']    =    $params['id'.$i];
                        $sss[$i]['title']    =    $params['title'.$i];
                        $sss[$i]['num']    =    $params['number'.$i];
                        $sss[$i]['unit_price']    =    $params['unit_price'.$i];
                        $sss[$i]['status']    =    $params['status'.$i];
                       if (empty($sss[$i]['title']) ||  empty($sss[$i]['num'])   ||empty($sss[$i]['unit_price']))
                       {
                                return  $this->error("必要条件不能为空");
                       }
                   }
                }
                $wc =   0;
                foreach ($sss as $key=>$value)
                {
                    if (isset($value['id']))
                    {
                        $iswhat=Db::name('assemble_meal')->where('id',$value['id'])->update($value);
                        if (isset($iswhat))
                        {
                            $wc += 1;
                        }
                    }
                }
                return  $this->success('修改成功');
            }else{
                return  $this->error('域名修改失败');
            }
        }
        $list   =  Db::name('assemble_suffix')->where('id',$ids)->find();
        $apis = $this->getApis();
        // $list['aid'] = empty($list['aid']) ? '--' : $apis[$list['aid']];
        $this->assign('data',$list);
        $lists   =   Db::name('assemble_suffix')->select();
        $this->assign('lists',$lists);
        $quanbu =   Db::name('assemble_meal')->where('sid',$ids)->select();
        $this->assign('qbs',$quanbu);
        return  $this->view->fetch();
    }
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            $insert['suffix']   =   $params['suffix'];
            $insert['dur_active']   =   $params['dur_active'];
            $insert['start_at']   =   strtotime($params['start_at']);
            $insert['end_at']   =   strtotime($params['end_at']);
            $insert['hd_title']   =   $params['hd_title'];
            $insert['reg_at']   =   strtotime($params['reg_at']);
            $time   =   time();
            
            // if(empty($params['aid'])){
            //     $this->error('请选择对应的接口商');
            // }else{
            //     $insert['aid'] = intval($params['aid']);
            // }

            if ($insert['start_at']>$insert['end_at']||$insert['end_at']-$insert['start_at']<86400|| $insert['dur_active']<1)
            {
                return  $this->error("活动时间不符合要求");
            }


            $www    =   Db::name('assemble_suffix')->where('end_at  > '.$insert['end_at'])->where('end_at  > '.$insert['start_at'])->where(['suffix' => $insert['suffix'] ])->find(); //,'aid' => $insert['aid']
            if (isset($www))
            {
                return    $this->error('时间范围有差异，请检查');
            }
            $sss    =   [];
            for ($i=0;$i<3;$i++)
            {
                $sss[$i]['title']    =    $params['title'.$i];
                $sss[$i]['num']    =    $params['number'.$i];
                $sss[$i]['unit_price']    =    $params['unit_price'.$i];
                $sss[$i]['status']    =    $params['status'.$i];
                if (empty($sss[$i]['title'])||empty($sss[$i]['num'])||empty($sss[$i]['unit_price']))
                {
                    return  $this->error("不能少于三个条件");
                }
            }
            $list   =   Db::name('assemble_suffix')->insertGetId($insert);
            $num    =   0;
            foreach($sss as $k=>$v)
            {
                $sss[$k]['sid']   =   $list;
                $num    +=  1;
            }
            $wode   =   Db::name('assemble_meal')->insertAll($sss);
            if (isset($wode)){
                return  $this->success("添加域名完成，添加套餐".$num."个");
            }
        }
        $list   =   Db::name('domain_houzhui')->field('name1')->select();
        $this->assign('list',$list);
        $lists   =   Db::name('assemble_suffix')->select();
        $this->assign('lists',$lists);
        return  $this->view->fetch();
    }
    public function del($ids    =   '')
    {
        $array  =   explode(',',$ids);
        foreach($array as $key=>$value)
        {
            $list   =   Db::name('assemble_suffix')->delete(['id'=>$value]);
        }
        if (isset($list))
        {
            return  $this->success('删除成功');
        }
        else{
            return  $this->error("删除失败");
        }
    }
    public function houzhui()
    {
        $time   =   time();
        $list   =   Db::name('assemble_suffix')->where('end_at    >'.$time.' and start_at  < '.$time)->field('suffix')->select();
        $data   =   [];
        foreach($list  as $key=>$value)
        {
            $data[$value['suffix']]   =   $value['suffix'];
        }
        return  $data;
    }
    public function hou()
    {
        $list   =   Db::name('assemble_suffix')->field('suffix')->select();
        foreach($list  as $key=>$value)
        {
            $data[$value['suffix']]   =   $value['suffix'];
        }
        return $data;
    }
}
