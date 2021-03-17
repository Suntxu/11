<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use think\Config;
/**
 * 举报管理
 *
 * @icon fa fa-user
 */
class Report extends Backend
{

    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_report_info');
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
            
            $total = Db::name('domain_report_info')->where($where)->count();

            $list = Db::name('domain_report_info')
                ->field('id,tit,type,uname,email,create_time,ip,status,sfz,remark,etime')
                ->where($where)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
                
            $fun = Fun::ini();
            foreach ($list as $k => &$v) {
                $v['show'] = $v['tit'];
                $v['num'] = count(explode(',',$v['tit']));
                $v['tit'] = '<span style="cursor:pointer;" id="show'.$v['id'].'" >查看</span>';
                $v['type'] = $fun->getStatus($v['type'],['--','涉黄暴力毒品赌博','传播恶意软件','钓鱼网站','注册信息不准确','其他违法网站']);
                $v['status'] = $fun->getStatus($v['status'],['未处理','<span style="color:orange">域名下架</span>','<span style="color:red">域名冻结</span>','<span style="color:gray">不做处理</span>']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    
    public function edit($ids=null){

        if($this->request->isAjax()){
            $params = $this->request->post("row/a");
            if(empty($params['status'])  || !intval($params['status'])){
                $this->error('请选择处理类型！');
            }
            $id = Db::name('domain_report_info')->where(['id' => $params['id'],'status' => 0])->value('id');
            if(empty($id)){
                $this->error('记录状态错误!');
            }
            $params['etime'] = time();
            $params['remark'] = empty($params['remark']) ? '已操作' : $params['remark'];
            Db::name('domain_report_info')->where('id',$params['id'])->update($params);
            $this->success('审核成功');
        }
        $data = Db::name('domain_report_info')
            ->field('id,tit,desc,imgpath,uname,sfzpath,create_time,status,sfz,etime,remark')
            ->where('id',$ids)
            ->find();
        $data['tit'] = explode(',',$data['tit']);
        $data['imgpath'] = explode(',',$data['imgpath']);
        // $data['type'] = Fun::ini()->getStatus($data['type'],['--','涉黄暴力毒品赌博','传播恶意软件','钓鱼网站','注册信息不准确','其他违法网站']);
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }


}
