<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 预释放列表
 *
 * @icon fa fa-user
 */
class Release extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        global $reserve_db;
        parent::_initialize();
        $this->model = Db::connect($reserve_db)->name('domain_pro_reserve_pre_'.date('Ymd'));
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
                    ->field('tit,hz,len,reg_time,del_time,money,type,icp_serial,icp_org,icp_name,icp_index,audit_time,access_pro,employ,weight,pr,ext_chain,qq_check,wx_check,gj')
                    ->where($where)->where($def)->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k => $v){
                $list[$k]['reg_time']=date('Y-m-d',$v['reg_time']);
                $list[$k]['del_time']=date('Y-m-d',$v['del_time']);
                if($v['pr']=='-1'){
                    $list[$k]['pr'] = $fun->getStatus($v['pr'],[-1 =>'待查']);
                }
                if($v['employ']=='-1'){
                    $list[$k]['employ'] = $fun->getStatus($v['employ'],[-1 =>'待查']);
                }
                if($v['weight']=='-1'){
                    $list[$k]['weight'] = $fun->getStatus($v['weight'],[-1 =>'待查']);
                }
                if($v['ext_chain']=='-1'){
                    $list[$k]['ext_chain'] = $fun->getStatus($v['ext_chain'],[-1 =>'待查']);
                }
                if($v['gj']=='0'){
                    $list[$k]['gj'] ='--';
                }
                $list[$k]['type'] = $fun->getStatus($v['type'],[1 =>'ali',88=>'ename',106=>'GD',67=>'xb',1000=>'怀米']);
                $list[$k]['access_pro'] = $fun->getStatus($v['access_pro'],[-1 => '待查',0=>'未知',1=>'阿里云',2=>'腾讯云',3=>'GAINET',4=>'西部数码',5=>'CNDNS',6=>'百度云']);
                $list[$k]['qq_check'] = $fun->getStatus($v['qq_check'],[-1 => '<span style="color:gray;">待查</span>',1=>'<span style="color:green;">未拦截</span>',2=>'<span style="color:red;">拦截</span>',3=>'<span style="color:orange;">未知</span>']);
                $list[$k]['wx_check'] = $fun->getStatus($v['wx_check'],[-1 => '<span style="color:gray;">待查</span>',1=>'<span style="color:green;">未拦截</span>',2=>'<span style="color:red;">拦截</span>',3=>'<span style="color:orange;">未知</span>']);
                if($v['icp_index'] == '历史存在'){
                    $list[$k]['icp_name'] = '<font style="text-decoration: line-through;" title="历史存在">'.$v['icp_name'].'</font>';
                }

              $list[$k]['group'] = $v['hz'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}


