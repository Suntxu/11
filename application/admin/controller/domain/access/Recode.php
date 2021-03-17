<?php

namespace app\admin\controller\domain\access;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名转回记录
 *
 * @icon fa fa-user
 */
class Recode extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 查看
     */
    public function index($ids = '')
    {
        //设置过滤方法
        $this ->request->filter('strip_tags');
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('domain_access')->alias('b')->join('domain_access_show d','d.aid = b.id')->join('domain_user u','u.id=b.userid')
                        ->where($where)->count();
                        
            $list = Db::name('domain_access')->alias('b')->join('domain_access_show d','d.aid = b.id')->join('domain_user u','u.id=b.userid')
                        ->field('d.domain,b.subdate,b.finishdate,b.audit,b.bath,b.email,b.reg_id,b.api_id,d.status,d.audittime,d.remark,u.uid')
                        ->where($where)->order($sort,$order)->limit($offset, $limit)
                        ->select();

            $arr = [];
            $fun = Fun::ini();
            $apis = $this->getApis(-1);
            foreach($list as $k => $v){
                $arr[$k]['d.domain'] = $v['domain'];
                $arr[$k]['b.subdate'] = $v['subdate'];
                $arr[$k]['b.finishdate'] = $v['finishdate'];
                $arr[$k]['b.email'] = $v['email'];
                $arr[$k]['b.bath'] = $v['bath'];

                if(isset($apis[$v['api_id']])){
                    $arr[$k]['b.reg_id'] = $apis[$v['api_id']]['regname'];
                    $arr[$k]['b.api_id'] = $apis[$v['api_id']]['tempid'];
                }else{
                    $arr[$k]['b.reg_id'] = '--';
                    $arr[$k]['a.api_id'] = '--';
                }

                $arr[$k]['b.audit'] = $fun->getStatus($v['audit'],['待审核','<span style="color:green">执行成功</span>','<span style="color:red">审核失败</span>','<span style="color:gray">任务执行中</span>','<span style="color:gray">用户取消</span>']);
                $arr[$k]['d.audittime'] = $v['audittime'];
                $arr[$k]['d.status'] = $fun->getStatus($v['status'],['待审核','转入中','转入成功','转入失败','已取消']);
                $arr[$k]['d.remark'] = $v['remark'];
                $arr[$k]['u.uid'] = $v['uid'];
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign('ids',$ids);
        return $this->view->fetch();
    }
}
