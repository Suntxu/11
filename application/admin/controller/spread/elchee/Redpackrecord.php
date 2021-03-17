<?php
namespace app\admin\controller\spread\elchee;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 红包使用记录
 *
 * @icon fa fa-user
 */
class Redpackrecord extends Backend
{
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
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
            // 获取已失效的搜索选项
            $sj = time();
            $defwhere = '';
            $filter = $this->request->get("filter", '');
            if($filter != ''){
                $filterArr = json_decode($filter,true);
                if(isset($filterArr['special_status'])){
                    if($filterArr['special_status'] == 0){
                        $defwhere = ' l.status = 0 and l.etime > '.$sj;
                    }elseif($filterArr['special_status'] == 1){
                        $defwhere = ' l.status = 1 ';
                    }else{
                        $defwhere = ' l.status = 0 and l.etime < '.$sj;
                    }
                }
            }
            $total = Db::name('domain_coupon_log')->alias('l')->join('domain_user u','l.userid=u.id')->join('domain_coupon c','l.cid=c.id','left')
                    ->where($where)->where($defwhere)
                    ->count();
            $list = Db::name('domain_coupon_log')->alias('l')->join('domain_user u','l.userid=u.id')->join('domain_coupon c','l.cid=c.id','left')
                    ->field('u.uid,c.title,c.satisfy_amount,c.rebate_amount,c.use_shop,c.term,c.use_type,l.use_type as use_type1,c.type,l.status,l.ctime,l.utime,l.bc,l.etime')
                    ->where($where)->where($defwhere)->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            $sql = $this -> setWhere();
            // 只搜索使用状态 也加入条件
            if($defwhere != ''){
                $sql .= '  ';
            }
            // 折扣总金额
            if(strlen($sql) == 12){
                $conm1 = 'SELECT sum(if(l.status = 0 && l.etime > '.$sj.',c.rebate_amount,0)) as w,sum(if(l.status = 1,c.rebate_amount,0)) as y,sum(if(l.status = 0 && l.etime < '.$sj.',c.rebate_amount,0)) as s FROM '.PREFIX.'domain_coupon_log as l LEFT JOIN '.PREFIX.'domain_coupon as c ON l.cid=c.id';
            }else{
                if($defwhere != ''){
                    $defwhere = ' and '.$defwhere ;
                }
                $conm1 = 'SELECT sum(if(l.status = 0 && l.etime > '.$sj.',c.rebate_amount,0)) as w,sum(if(l.status = 1,c.rebate_amount,0)) as y,sum(if(l.status = 0 && l.etime < '.$sj.',c.rebate_amount,0)) as s FROM '.PREFIX.'domain_coupon_log as l LEFT JOIN '.PREFIX.'domain_coupon as c ON l.cid=c.id INNER JOIN '.PREFIX.'domain_user as u ON l.userid=u.id '.$sql.$defwhere;
            }
            $res1 = Db::query($conm1);
            $wsy = sprintf('%2.f',($res1[0]['w'] / 100));
            $ysy = sprintf('%2.f',($res1[0]['y'] / 100));
            $ysx = sprintf('%2.f',($res1[0]['s'] / 100));
            $arr = [];
            foreach($list as $k => $v){
                $arr[$k]['u.uid'] = $v['uid'];
                $arr[$k]['c.title'] = $v['title'];
                $arr[$k]['c.term'] = $v['term'];
                $arr[$k]['l.ctime'] = $v['ctime'];
                $arr[$k]['l.utime'] = $v['utime'];
                $arr[$k]['l.bc'] = $v['bc'] ? $v['bc'] : '--' ;
                $arr[$k]['c.type'] = $fun->getStatus($v['type'],['所有用户']);
                $arr[$k]['l.use_type'] = $fun->getStatus($v['use_type1'],['不限','满减']);
                $arr[$k]['c.use_type'] = $fun->getStatus($v['use_type'],['--','一口价']);
                $arr[$k]['c.rebate_amount'] = sprintf('%.2f',($v['rebate_amount']/100));
                $arr[$k]['c.satisfy_amount'] = sprintf('%.2f',($v['satisfy_amount']/100));
                $arr[$k]['wsy'] = $wsy;
                $arr[$k]['ysy'] = $ysy;
                $arr[$k]['ysx'] = $ysx;
                if($v['use_shop'] == '0'){
                    $arr[$k]['c.use_shop'] = '不限';
                }else{
                    $shop = Db::name('storeconfig')->field('shopname')->where(['userid'=>$v['use_shop']])->find();
                    $arr[$k]['c.use_shop'] = $shop['shopname'];
                }
                if($v['status'] == 1){
                  $arr[$k]['special_status'] = '已使用';
                }else if($v['status'] == 0){
                    if($v['etime'] < $sj){
                      $arr[$k]['special_status'] = '已失效';
                    }else{
                      $arr[$k]['special_status'] = '未使用';
                    }
                }
            }
            $result = array("total" => $total, "rows" => $arr);
            return json($result);
        }
        return $this->view->fetch();
    }
}
