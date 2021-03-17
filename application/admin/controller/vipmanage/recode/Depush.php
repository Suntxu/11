<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名交易记录
 *
 * @icon fa fa-user
 */
class Depush extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('push_record');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('r')->join('domain_user u','r.suserid=u.id','left')->join('domain_user u1','r.tuserid=u1.id','left')
                        ->where($where)->count();
            $list = $this->model->alias('r')->join('domain_user u','r.suserid=u.id','left')->join('domain_user u1','r.tuserid=u1.id','left')
                        ->field('u.uid,r.id,r.tuserid,r.status,r.pushtime,r.remark,r.money,r.jytime,r.tit,u1.uid as tuid')
                        ->where($where)->order($sort,$order)->limit($offset, $limit)->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();

            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money) as n FROM '.PREFIX.'push_record where status = 2 ';
            }else{
                $conm = 'SELECT sum(r.money) as n FROM '.PREFIX.'push_record as r left join '.PREFIX.'domain_user as u on r.suserid=u.id left join '.PREFIX.'domain_user as u1 on r.tuserid=u1.id '.$sql.' and r.status = 2';
            }
           
            $res = Db::query($conm);
            $fun = Fun::ini();
            foreach($list as $k => $v){
                $list[$k]['r.status'] = $fun->getStatus($v['status'],['push中','对方已拒绝','对方已接收push','已撤销']);
                $list[$k]['u1.uid'] = $v['tuid'];
                $list[$k]['u.uid'] = $v['uid'];
                $list[$k]['r.money'] = sprintf('%.2f',$v['money']);
                $list[$k]['u.remark'] = $v['remark'];
                $list[$k]['zje'] = $res[0]['n'];
                $list[$k]['tit'] = '<span style="cursor:pointer;" id="show'.$v['id'].'" >查看</span>';
                $list[$k]['r.tit'] = $v['tit'];
                $list[$k]['domainLen'] = count(explode(',',rtrim($v['tit'],',')));
                $list[$k]['r.id'] = $v['id'];
                // $list[$k]['r.type'] = $fun->getStatus($v['type'],['普通','<font color="orange">回收</font>']);
            }   
            
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign([
            'ids' => $this->request->get('u.uid'),
            'id'  => $this->request->get('r_id'),
        ]);
        return $this->view->fetch();
    }

}
