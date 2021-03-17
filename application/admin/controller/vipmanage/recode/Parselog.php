<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名解析操作记录
 *
 * @icon fa fa-user
 */
class Parselog extends Backend
{

    protected $model = null;

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
    public function index()
    {

        
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $filter = $this->request->param('filter');

            if($filter == '{}'){
                $total = Db::name('action_record')->count();
            }else{
                $total = Db::name('action_record')->alias('r')->join('domain_user u','r.userid=u.id')
                    ->where($where)
                    ->count();
            }
            $list = Db::name('action_record')->alias('r')->join('domain_user u','r.userid=u.id')
                ->field('r.tit,r.remark,u.uid,r.newstime,r.uip')
                ->where($where)->order($sort,$order)->limit($offset, $limit)
                ->select();

            $arr = [];
            foreach($list as $k => $v){
                $arr[$k]['u.uid'] =$v['uid'];
                $arr[$k]['r.tit'] =$v['tit'];
                $arr[$k]['remark'] =$v['remark'];
                $arr[$k]['r.uip'] =$v['uip'];
                $arr[$k]['r.newstime'] =$v['newstime'];
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign([
            'uid' => $this->request->get('u_uid'),
            'tit' => $this->request->get('r_tit'),
        ]);
        return $this->view->fetch();
    }

}
