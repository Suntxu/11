<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
/**
 * 人工充值记录
 *
 * @icon fa fa-user
 */
class Recharge extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_dingdang');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   


            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $def = ' d.bz = "人工充值" and d.ifok = 1 ';
            $total = $this->model->alias('d')->join('admin a','d.admin_id = a.id')->join('domain_user u','u.id=d.userid')->where($def)->where($where)->count();

            $list = $this->model->alias('d')->join('admin a','d.admin_id = a.id')->join('domain_user u','u.id=d.userid')
                        ->field('d.ddbh,d.money1,d.sj,d.userid,u.uid,d.wxddbh,a.nickname,remark')
                        ->where($def)->where($where)->order($sort,$order)->limit($offset, $limit)
                        ->select();

            //总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_dingdang d where '.$def;
                ;
            }else{
                $conm = 'SELECT sum(d.money1) as n FROM '.PREFIX.'domain_dingdang as d inner join '.PREFIX.'admin a on a.id = d.admin_id inner join '.PREFIX.'domain_user as u on d.userid=u.id '.$sql.' and '.$def;
            }
            $res = Db::query($conm);
            foreach($list as &$v){
                $v['zje'] = sprintf('%.2f',$res[0]['n']);
                $v['d.sj'] = $v['sj'];
                $v['d.money1'] = $v['money1'];
                $v['a.nickname'] = $v['nickname'];
            }   
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
