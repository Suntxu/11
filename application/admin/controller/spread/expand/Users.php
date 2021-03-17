<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use think\Db;

/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Users extends Backend
{

    protected $noNeedRight = ['getSelectName'];

    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_user');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('u')->join('admin a','u.topspreader=a.id','left')->join('spread_channel c1','c1.id=u.channel','left')
                    ->where($where)->where('u.topspreader!=0')
                    ->count();
            $list = $this->model->alias('u')->join('admin a','u.topspreader=a.id','left')->join('spread_channel c1','c1.id=u.channel','left')
                    ->field('u.id,u.uid,u.sj,u.mot,u.zt,u.nc,a.nickname,(select sum(money1) from '.PREFIX.'domain_dingdang where userid=u.id and ifok = 1 and topspreader != 0) as je,c1.name as ydmc ')
                    ->where($where)->where('u.topspreader!=0')
                    ->order($sort,$order)
                    ->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang WHERE topspreader != 0';
            }else{
                $conm = 'SELECT sum((select sum(money1) from '.PREFIX.'domain_dingdang where userid=u.id and ifok = 1 and topspreader != 0)) as n FROM '.PREFIX.'domain_user u left join '.PREFIX.'admin a ON u.topspreader=a.id '.$sql.' and u.topspreader!=0 ';
            }
            $res = Db::query($conm);
            foreach($list as $k => $v){
                if(empty($v['je'])){
                    $list[$k]['je'] = 0;
                }else{
                    $list[$k]['je'] = sprintf('%.2f',$v['je']);
                }
               //渠道名称
               $list[$k]['zje'] = $res[0]['n'];
                if($v['zt'] == 1){
                    $list[$k]['zt'] = '正常';
                }elseif($v['zt'] == 3){
                    $list[$k]['zt'] = '冻结';
                }elseif($v['zt'] == 2){
                    $list[$k]['zt'] = '邮箱未激活';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this -> view -> assign('id',$this->request->get('nickname'));
        return $this->view->fetch();
    }
    /**
     * 记录查询
     */
    public function record(){
        $this->view->assign('uid',$this->request->get('uid'));
        return $this->view->fetch();
    }
}


