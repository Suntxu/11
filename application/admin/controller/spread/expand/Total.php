<?php
namespace app\admin\controller\spread\expand;
use app\common\controller\Backend;
use think\Db;
/**
 * 访问量统计
 *
 * @icon fa fa-user
 */
class Total extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {   

            list($where, $sort, $order, $offset, $limit,$group,$admin) = $this->buildparams();
            $model = Db::name('total_'.date('Y'));
            $aInfo = $this->getAdminNickname();

            $def = '';

            if($admin){
                $admin_id = isset($aInfo[$admin]) ? $aInfo[$admin] : 0;
                $def = ' t.link = '.$admin_id;
            }
            if(empty($group)){
                $total = $model->alias('t')->join('domain_link l','t.lid=l.id','left')
                    ->where($where)->where($def)
                    ->count();
                $list = $model->alias('t')->join('domain_link l','t.lid=l.id','left')
                    ->field('t.*,l.alink')->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }else{
                if($group == 'cookie'){
                    $gr = 't.cookie';
                }else{
                    $gr = 't.ip,DATE_FORMAT(t.create_time,"%Y-%m-%d")';
                }
                $total = $model->alias('t')->join('domain_link l','t.lid=l.id','left')
                    ->where($where)->where($def)
                    ->group($gr)
                    ->count();
                $list = $model->alias('t')->join('domain_link l','t.lid=l.id','left')
                    ->field('t.*,l.alink')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->group($gr)
                    ->limit($offset, $limit)
                    ->select();
            }
            $sp = $this->getChannelName();
            $aInfo = array_flip($aInfo);
            foreach($list as &$v){
                $v['top'] = isset($sp[$v['top']]) ? $sp[$v['top']] : '--';
                $v['special_condition'] = isset($aInfo[$v['link']]) ? $aInfo[$v['link']] : '--';
            }

            $result = array("total" => $total,"rows" => $list);
            return json($result);
        }
        $this->view->assign(['gro'=>$this->request->get('gro')]);
        return $this->view->fetch();
    }
}

