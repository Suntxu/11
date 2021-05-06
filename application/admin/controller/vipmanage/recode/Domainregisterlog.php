<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * DNS解析记录
*/
class Domainregisterlog extends Backend
{
    protected $noNeedRight = ['*'];
    protected $model = null;

    public function _initialize()
    {
        $this->model = Db::name('Task_record');
        parent::_initialize();
    }

    public function index(){
        if ($this->request->isAjax()) {

            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);
            if(empty($filter['d.tit']) ){
                $this->error('请设置搜索条件域名后查询数据');
            }

            list($where, $sort, $order, $offset, $limit ) = $this->buildparams();
            $total = $this->model->table(PREFIX.'Task_record')->alias('r')
                ->join(PREFIX.'Task_Detail'.' d','r.id = d.taskid','right')
                ->join('domain_user u','r.userid=u.id')
                ->where($where)
                ->where('r.tasktype = 2')
                ->count();

            $list = $this->model->table(PREFIX.'Task_record')->alias('r')
                ->join(PREFIX.'Task_Detail'.' d','r.id = d.taskid','right')
                ->join('domain_user u','r.userid=u.id')
                ->field('r.id,r.uip,r.status,r.createtime,uid,d.ErrorMsg,d.tit,d.TaskStatusCode,d.money,d.api_id,d.hz,d.CreateTime,r.a_type,r.cos_price')
                ->where($where)
                ->where('r.tasktype = 2')
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $apis = $this->getApis(-1);
//            $zcs = $this->getCates('api',true);
            $fun = Fun::ini();
            foreach ($list as $k => $v){
                $list[$k]['d.api_id'] = isset($v['api_id']) ? $apis[$v['api_id']]['tit'] : '';
//                $list[$k]['r.zcs'] = isset($v['zcs']) ? $zcs[$v['zcs']] : '';
                $list[$k]['r.status'] = $fun->getStatus($v['status'],['执行中','执行成功']);
                $list[$k]['d.TaskStatusCode'] = $fun->getStatus($v['TaskStatusCode'],['等待执行','执行中','执行成功','执行失败',9 => '已退款']);
                $list[$k]['d.tit'] = $v['tit'];
                $list[$k]['u.uid'] = $v['uid'];
                $list[$k]['d.ErrorMsg'] = $v['ErrorMsg'];
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['d.CreateTime'] = $v['CreateTime'];
                $list[$k]['d.money'] = $v['money'];
                $list[$k]['r.uip'] = $v['uip'];
                $list[$k]['d.hz'] = $v['hz'];
                $list[$k]['r.a_type'] = $fun->getStatus($v['a_type'],['普通','拼团','限量','注册包']);
            }
            return json(["total" => $total, "rows" => $list]);
        }
        return $this->view->fetch();
    }
}