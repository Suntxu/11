<?php
namespace app\admin\controller\spread\elchee;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 素材统计
 *
 * @icon fa fa-user
 */
class Materiallog extends Backend
{
    /**
     * 
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
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();

            $def = ' 1 = 1 ';
            if($group){
                $def .= ' and m.link = "'.$group.'" or l.other = "'.$group.'"  ';
            }
            $year = date('Y');
            $model = Db::name('domain_promotion_material_log_'.$year);
            $total = $model->alias('l')->join('domain_promotion_material m','l.mid=m.id','left')->join('domain_user u','u.id=l.puserid')->join('domain_user u1','u1.id=l.userid','left')
                    ->where($where)->where($def)
                    ->count();
            $list = $model->alias('l')->join('domain_promotion_material m','l.mid=m.id','left')->join('domain_user u','u.id=l.puserid')->join('domain_user u1','u1.id=l.userid','left')
                    ->field('u.uid,u1.uid as uuid,m.title,l.userid,l.puserid,l.mark,l.type,l.ip,l.ctime,m.link,m.type as mtype,l.other,l.mid')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            // 单独统计 多对多关系
            $toNum = $model->alias('l')->join('domain_promotion_material m','l.mid=m.id','left')->join('domain_user u','u.id=l.puserid')->join('domain_user u1','u1.id=l.userid','left')
                    ->field('l.puserid,l.userid')
                    ->where($where)->where($def)->where('l.type',2)
                    ->select();
            //总佣金
            $puserids = array_column($toNum,'puserid');
            $zyj = Db::name('spreader_flow')->where('type',1)->whereIn('userid',array_unique($puserids))->sum('yj');
            //总充值金额
            $userids = array_column($toNum,'userid');
            //切割userid
//            $dq = array_chunk($userids,300);
//            $dcoun = '( 1 = 1 ';
//            foreach($dq as $v){
//                $dcoun .= 'or userid in ('.implode(',',$v).') ';
//            }
//            $dcoun .= ')';
            $zje = Db::name('domain_dingdang')->where('ifok',1)->whereIn('userid',$userids)->sum('money1');
            //行内充值金额 佣金
            //获取本页的userid
            $selfids = array_intersect(array_column($list,'userid'),$userids);
            $czje = Db::name('domain_dingdang')->where('ifok',1)->whereIn('userid',$selfids)->group('userid')->column('sum(money1)','userid');
            $selfpids = array_unique(array_intersect(array_column($list,'puserid'),$puserids));
            $yj = Db::name('spreader_flow')->where('type',1)->whereIn('userid',$selfpids)->group('userid')->column('sum(yj)','userid');
            //根据条件统计总金额
            $sql = $this -> setWhere();

            if(strlen($sql) == 12){
                $conm = 'SELECT count(case l.type=1 when 1 then 0 end) as wzc,count(case l.type=2 when 1 then 0 end) as zc FROM '.PREFIX.'domain_promotion_material_log_'.$year.' l left join  '.PREFIX.'domain_promotion_material m on m.id=l.mid where'.$def;
            }else{
                $conm = 'SELECT count(case l.type=1 when 1 then 0 end) as wzc,count(case l.type=2 when 1 then 0 end) as zc FROM '.PREFIX.'domain_promotion_material_log_'.$year.' l left join  '.PREFIX.'domain_promotion_material m on m.id=l.mid inner join '.PREFIX.'domain_user u on u.id=l.puserid left join '.PREFIX.'domain_user u1 on u1.id=l.userid '.$sql.' and '.$def;
            }
            $res = Db::query($conm);
            $fun = Fun::ini();
            $marketings = \think\Config::get('self_marketing');
            foreach($list as $k => $v){
                $list[$k]['u1.uid'] = $v['uuid'];
                $list[$k]['u.uid'] = $v['uid'];

                if($list[$k]['mid'] == 9){
                    $list[$k]['group'] = $v['other'];
                }else{
                    $list[$k]['group'] = $v['link'];
                }
                if(isset($marketings[$v['uid']])){
                    $list[$k]['u.uid'].= ' -- '.$marketings[$v['uid']];
                }
                $list[$k]['l.type'] = $fun->getStatus($v['type'],[1=>'未注册','已注册']);
                $list[$k]['m.type'] = $fun->getStatus($v['mtype'],['普通','专属']);
                $list[$k]['yzc'] = $res[0]['zc'];
                $list[$k]['wzc'] = $res[0]['wzc'];
                $list[$k]['czje'] = isset($czje[$v['userid']]) ? sprintf('%.2f',$czje[$v['userid']]) : 0.00;
                $list[$k]['yj'] = isset($yj[$v['puserid']]) ? sprintf('%.2f',$yj[$v['puserid']]) : 0.00;
                $list[$k]['zyj'] = $zyj;
                $list[$k]['zje'] = $zje;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        $this->view->assign('id',$this->request->get('mid'));
        return $this->view->fetch();
    }



}
