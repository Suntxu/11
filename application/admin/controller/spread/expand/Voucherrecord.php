<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;


/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Voucherrecord extends Backend
{

    protected $relationSearch = false;
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_voucherrecord');
    }

    /**
     * 
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->alias('c')
                    ->join('domain_voucher v','c.voucher_id=v.id')
                    ->join('domain_user u','u.id=v.userid')
                    ->join('admin a','a.id=v.topid')
                    ->where($where)
                    ->count();
            $list = $this->model
                    ->alias('c')
                    ->join('domain_voucher v','c.voucher_id=v.id')
                    ->join('domain_user u','u.id=v.userid')
                    ->join('admin a','a.id=v.topid')
                    ->field('c.*,v.bh,u.uid,a.nickname')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $list = collection($list)->toArray();
            //面额总计
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm1 = 'SELECT sum(addmoney) as n FROM '.PREFIX.'domain_voucherrecord';
            }else{
                $conm1 = 'SELECT sum(c.addmoney) as n FROM '.PREFIX.'domain_voucherrecord as c left join '.PREFIX.'domain_voucher as v on c.voucher_id=v.id left join  '.PREFIX.'domain_user as u on v.userid = u.id  left join '.PREFIX.'admin as a on a.id = v.topid'.$sql;
            }
            $res1 = Db::query($conm1);
            foreach($list as $k=>$v){
               $list[$k]['c.type'] = Fun::ini()->getStatus($v['type'],['系统发放','域名注册']);
               $list[$k]['mezje'] = $res1[0]['n'];
               $list[$k]['c.createtime'] =$v['createtime'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('bh',$this->request->get('bh'));
        return $this->view->fetch();
    }

}
