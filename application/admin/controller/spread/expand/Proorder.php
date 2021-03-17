<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use think\Db;

/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Proorder extends Backend
{

    /**
     * 充值记录
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_dingdang');
    }

    /**
     * 
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $u_where = 'd.topspreader != 0';
            $total = $this->model->alias('d')->join('admin a','d.topspreader=a.id')->join('domain_user u','d.userid=u.id')->join('spread_channel c','d.channel=c.id')
                    ->where($where)->where($u_where)
                    ->count();
            //总金额
            $nu = $list = $this->model->alias('d')->join('admin a','d.topspreader=a.id')->join('domain_user u','d.userid=u.id')->join('spread_channel c','d.channel=c.id')
                ->where($where)->where($u_where)
                ->sum('d.money1');

            $list = $this->model->alias('d')->join('admin a','d.topspreader=a.id')->join('domain_user u','d.userid=u.id')->join('spread_channel c','d.channel=c.id')
                    ->field('d.id,d.ddbh,d.money1,d.sj,d.ifok,d.userid,a.username,c.name as ydmc,u.uid,u.sj as usj')
                    ->where($where)->where($u_where)
                    ->order('d.'.$sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach($list as $k => $v){
                $list[$k]['d.sj'] = $v['sj'];
                $list[$k]['u.sj'] = $v['usj'];
                $list[$k]['d.money1'] = $v['money1']; 
                $list[$k]['zje'] = $nu;
               //渠道名称
               if($v['ifok'] == 1 ){
                    $list[$k]['ifok'] = '成功';
               }elseif($v['ifok'] == '0' ){
                    $list[$k]['ifok'] = '失败';
               }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $requ = $this->request->get();
        $this->view->assign([
            'cel' => empty($requ['cel']) ? '' : $requ['cel'],
            'topspreader' => empty($requ['topspreader']) ? '' : $requ['topspreader'],
            'userid' =>empty($requ['userid']) ? '' : $requ['userid'],
            'ifok' => empty($requ['ifok']) ? '' : $requ['ifok'],
        ]);
        return $this->view->fetch();
    }
}


