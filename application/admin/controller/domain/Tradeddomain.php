<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 已成交域名列表
 *
 * @icon fa fa-user
 */
class Tradeddomain extends Backend
{

    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_order');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {

            list($where, $sort, $order, $offset, $limit,$hz,$rel) = $this->buildparams();
            $defindwhere = ' c.status = 1 ';

            if($hz){
                $defindwhere .= ' and REPLACE(c.tit,substring_index(c.tit,".",1),"") = "'.$hz.'"  ';
            }
            if($rel == 1){
                $defindwhere .= ' and relshop_userid = 0 ';
            }elseif($rel == 2){
                $defindwhere .= ' and relshop_userid > 0 ';
            }

            $total = $this->model
                    ->alias('c')->join('domain_user u','c.userid=u.id','left')->join('domain_user s','c.selleruserid=s.id','left')->join('domain_user u1','c.relshop_userid=u1.id','left')
                    ->where($where)->where($defindwhere)
                    ->count();
            $list = $this->model
                    ->alias('c')->join('domain_user u','c.userid=u.id','left')->join('domain_user s','c.selleruserid=s.id','left')->join('domain_user u1','c.relshop_userid=u1.id','left')
                    ->field('c.id,c.tit,s.uid as suid,c.paytime,u.uid as uuid,c.money,c.bc,c.pack,c.sxf,c.qmoney,u1.uid as u1id')
                    ->where($where)->where($defindwhere)->order($sort, $order)->limit($offset, $limit)
                    ->select();
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12 && empty($defindwhere)){
                $conm = 'SELECT sum(money) as n,sum(sxf) as f FROM '.PREFIX.'domain_order WHERE status = 1';
                $conm1 = 'SELECT count(DISTINCT userid) AS n FROM '.PREFIX.'domain_order WHERE status = 1';
            }else{
                if($defindwhere){
                    $defindwhere = ' and '.$defindwhere;
                }
                $conm = 'SELECT sum(c.money) as n,sum(c.sxf) as f FROM '.PREFIX.'domain_order as c LEFT JOIN '.PREFIX.'domain_user as u ON c.userid=u.id LEFT JOIN '.PREFIX.'domain_user as s ON c.selleruserid=s.id left join '.PREFIX.'domain_user u1 on c.relshop_userid = u1.id '.$sql.' AND c.status = 1'.$defindwhere;
                $conm1 = 'SELECT count(DISTINCT c.userid) AS n FROM '.PREFIX.'domain_order as c LEFT JOIN '.PREFIX.'domain_user as u ON c.userid=u.id LEFT JOIN '.PREFIX.'domain_user as s ON c.selleruserid=s.id left join '.PREFIX.'domain_user u1 on c.relshop_userid = u1.id '.$sql.' AND c.status = 1 '.$defindwhere;
            }
            
            $res = Db::query($conm);
            $res1 = Db::query($conm1);
            // 字段 交易类型(连接的domain_pro的jyfs 或者在购物车增加一个字段) 交易状态
            $arr = [];
            foreach($list as $k=>$v){

                $arrs = explode('.',$v['tit']);
                $arr[$k]['group'] =  str_replace($arrs[0],'',$v['tit']);
                $arr[$k]['c.tit'] = $v['tit'];
                $arr[$k]['s.uid'] = $v['suid'];
                $arr[$k]['c.paytime'] = $v['paytime'];
                $arr[$k]['u.uid'] = $v['uuid'];
                $arr[$k]['c.money'] = $v['money'];
                $arr[$k]['zje'] = $res[0]['n'];
                $arr[$k]['people'] = $res1[0]['n'];
                $arr[$k]['c.bc'] = $v['bc'];
                $arr[$k]['pack'] = $v['pack'];
                $arr[$k]['id'] = $v['id'];
                $arr[$k]['sxf'] = sprintf('%.2f',$v['sxf']);
                $arr[$k]['zsfx'] = $res[0]['f'];
                $arr[$k]['u1.uid'] = $v['u1id'];
                $arr[$k]['special_condition'] = '';
                // 打包数量
                if(empty($v['pack'])){
                    $arr[$k]['pack_num'] ='--';
                    $arr[$k]['c.pack'] = 'not exists';  
                }else{
                    $pack = explode(',',$v['pack']);
                    $arr[$k]['pack_num'] = '<span style="cursor:pointer;color:#72afd2;" id="show'.$v['id'].'" >'.count($pack).'</span>';
                    $arr[$k]['c.pack'] = 'exists';
                }
            }
            unset($list);
            //根据条件统计总金额
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        $this->view->assign('suid',$this->request->get('s.uid'));
        return $this->view->fetch();
    }


}
