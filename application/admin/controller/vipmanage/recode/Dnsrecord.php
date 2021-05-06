<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * DNS解析记录
*/
class Dnsrecord extends Backend
{
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::table(PREFIX.'Task_record');
    }

    public function index(){
        if ($this->request->isAjax()) {
            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);

            if(empty($filter['d.tit']) && empty($filter['u.uid']) ){
                $this->error('请设置搜索条件域名或用户后查询数据');
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('r')
                ->join(PREFIX.'Task_Detail_3 d','r.id = d.taskid','left')
                ->join('domain_user u','r.userid=u.id','left')
                ->join('domain_pro_n dp','dp.tit=d.tit','left')
                ->where($where)
                ->where('r.tasktype = 3')
                ->count();
            $list = Db::table(PREFIX.'Task_record')->alias('r')
                ->join(PREFIX.'Task_Detail_3 d','r.id = d.taskid','left')
                ->join('domain_user u','r.userid=u.id','left')
                ->join('domain_pro_n dp','dp.tit=d.tit','left')
                ->field('r.id,r.createtime,u.uid,d.tit,d.api_id,r.status,dp.zcs,d.CreateTime,d.TaskStatusCode,r.remark,d.ErrorMsg')
                ->where('r.tasktype = 3')
                ->order($sort,$order)
                ->where($where)
                ->limit($offset, $limit)
                ->select();

            $apis = $this->getApis(-1);
            $zcs = $this->getCates('api',true);
            $fun = Fun::ini();
            foreach ($list as $k => $v){

                $list[$k]['d.api_id'] = isset($apis[$v['api_id']]) ? $apis[$v['api_id']]['tit'] : '';
                $list[$k]['dp.zcs'] = isset($zcs[$v['zcs']]) ? $zcs[$v['zcs']] : '';
                $list[$k]['r.status'] = $fun->getStatus($v['status'],['执行中','执行完成']);
                $list[$k]['d.TaskStatusCode'] = $fun->getStatus($v['TaskStatusCode'],['等待执行','执行中','执行成功','执行失败']);
                $list[$k]['d.tit'] = $v['tit'];
                $list[$k]['u.uid'] = $v['uid'];
                $list[$k]['d.ErrorMsg'] = $v['ErrorMsg'];
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['d.CreateTime'] = $v['CreateTime'];
                $list[$k]['r.remark'] = $v['remark'];
            }

            return json(["total" => $total, "rows" => $list]);
        }

        return $this->view->fetch();
    }
}