<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 预定域名过期保留删除记录
 *
 * @icon fa fa-user
 */
class Reservedomain extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        global $reserve_db;
        parent::_initialize();
        $this->model = Db::connect($reserve_db)->name('domain_pro_reserve');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $def = '';
            if($group){
              $def = 'hz = "'.ltrim($group,'.').'"';
            }
            $total = $this->model->where($where)->where($def)->count();
            
            $list = $this->model
                    ->field('tit,hz,len,reg_time,del_time,icp_serial,icp_org,icp_index,access_pro,employ,weight,pr,icp_name')
                    ->where($where)->where($def)->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
//            $dtype = $fun->getDomainType();
            foreach($list as $k => $v){
                $list[$k]['access_pro'] = $fun->getStatus($v['access_pro'],[-1 => '待查','未知','阿里云','腾讯云','GAINET','西部数码','CNDNS','百度云']);
                if($v['icp_index'] == '历史存在'){
                    $list[$k]['icp_name'] = '<font style="text-decoration: line-through;" title="历史存在">'.$v['icp_name'].'</font>';
                }
//
//                if(empty($v['domain_type']) || $v['domain_type'] == 'none'){
//                  $list[$k]['domain_type'] = '--';
//                }else{
//                  $twot = substr($v['domain_type'],0,2);
//                  if(empty($dtype[$v['domain_type'][0]])){
//                      $list[$k]['domain_type'] = empty($dtype[$twot][1][$v['domain_type']]) ? '--' : $dtype[$twot][1][$v['domain_type']];
//                  }else{
//                      $list[$k]['domain_type'] = $dtype[$v['domain_type'][0]];
//                  }
//              }

              $list[$k]['group'] = $v['hz'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
