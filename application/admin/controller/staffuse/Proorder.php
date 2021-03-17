<?php

namespace app\admin\controller\staffuse;

use app\common\controller\Backend;
use think\Db;

/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Proorder extends Backend
{

    protected $relationSearch = false;
    /**
     * User模型对象
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
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $wh = ['d.topspreader'=>$this->auth->id];
            $total = $this->model->alias('d')->join('domain_user u','d.userid=u.id','left')->join('spread_channel c','d.channel=c.id','left')
                    ->where($where)->where($wh)
                    ->count();

            $list = $this->model->alias('d')->join('domain_user u','d.userid=u.id','left')->join('spread_channel c','d.channel=c.id','left')
                    ->field('d.id,d.ddbh,d.money1,d.sj,d.userid,d.ifok,c.name as ydmc,u.uid')
                    ->where($where)->where($wh)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $nu = $this->model->alias('d')->where($where)->where($wh)->sum('money1');

            foreach($list as $k => $v){
               $list[$k]['d.sj'] = $v['sj']; 
               $list[$k]['d.money1'] = $v['money1']; 
               $list[$k]['zje'] = $nu;
               if($v['ifok'] == 1 ){
                    $list[$k]['ifok'] = '成功';
               }elseif($v['ifok'] == '0' ){
                    $list[$k]['ifok'] = '失败';
               }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('userid',$this->request->get('userid'));
        return $this->view->fetch();
    }
}


