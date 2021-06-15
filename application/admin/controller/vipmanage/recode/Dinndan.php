<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 充值记录
 *
 * @icon fa fa-user
 */
class Dinndan extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('flow_record');
    }
    
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$uid) = $this->buildparams();
            $def = '';
            if($uid){
              $uid = Fun::moreRow($uid);
              if($uid){
                $def .= 'u.uid in ("'.implode('","',$uid).'")';
              }
            }
            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id','left')
                          ->where($where)->where($def)->count();
            $list = $this->model->alias('r')->join('domain_user u','r.userid=u.id','left')
                    ->field('u.uid,r.userid,r.sj,r.product,r.subtype,r.uip,r.balance,r.money,r.infoid,r.info')
                    ->where($where)->where($def)->order($sort, $order)->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            foreach($list as $k => $v){
                if(mb_strlen($v['info']) > 15){
                    $list[$k]['info'] = $fun->returntitdian($v['info'],15).'<span class="show_value" style="cursor:pointer;color:#3c8dbc;" onclick="showRemark(\''.$v['info'].'\')">查看</span>';
                }
                // 拼链接
               switch ($v['subtype']) {
                    case 0:
                        //判断是否合作方订单
                        if(strlen($v['infoid']) == 14 && substr($v['infoid'],0,1) == 'F'){
                          $show = '/admin/spread/elchee/ordersagentout?u.uid='.$v['uid'].'&c.bc='.$v['infoid'];
                        }else{
                          $fin = Db::name('domain_order')->where('bc',$v['infoid'])->column('userid');
                          if(in_array($v['userid'],$fin)){
                            $u = 'u.uid=';
                          }else{
                            $u = 's.uid=';
                          }
                          $show = '/admin/vipmanage/recode/deallog?'.$u.$v['uid'].'&bc='.$v['infoid'].'&c.sj= &group='.$v['info'];
                        }
                        
                       break;
                    case 1:
                        $show = '/admin/vipmanage/recode/depush?r.id='.$v['infoid'];
                       break;
                    case 2:
                        $show = '/admin/vipmanage/recode/transfershow?type=2&tid='.$v['infoid'];
                       break;
                    case 3:
                        $show = '/admin/domain/into/intolist?ids='.$v['infoid'];
                       break;
                    case 4:
                    case 5:
                        if(strpos($v['info'],'资金扣除')){
                            $show = '/admin/vipmanage/recode/margin?id='.$v['infoid'];
                        }else{
                            $show = '/admin/vipmanage/recode/payrank?id='.$v['infoid'];
                        }
                       break;
                    case 6:
                        $show = '/admin/vipmanage/bill/bill?ids='.$v['infoid'];
                       break;
                    case 7:
                        $show = '/admin/vipmanage/realaudit?ids='.$v['infoid'];
                       break;
                    case 8:
                        $show = '/admin/vipmanage/tx?ids='.$v['infoid'];
                       break;
                    case 10:
                        $show = '/admin/spread/withdraw?id='.$v['infoid'];
                       break;
                    case 11:
                        $show = '/admin/vipmanage/recode/transfershow?type=4&tid='.$v['infoid'];
                       break;
                    case 18:
                        $show = '/admin/domain/recycle/recylist?group=HS'.$v['infoid'];
                       break;
                   case 20:
                        $show = '/admin/oprecord/delrecord?id='.$v['infoid'];
                       break;
                    case 21:
                        $show = '/admin/activity/welfare/orders?id='.$v['infoid'];
                       break;
                   default:
                       $show = '';
                       break;
               }
               if($show){
                    $list[$k]['showurl'] = '<a href="'.$show.'" class="dialogit"  title="详情">详情</a>';
               }else{
                    $list[$k]['showurl'] = '无连接';
               }
               if($v['subtype'] == '15' ||$v['subtype'] == '13' ){
                  $list[$k]['info'] = '<a class="dialogit" href="/admin/domain/reserve/auctionlog?tit='.$v['info'].'">'.$v['info'].'</a>';
               }
               $list[$k]['r.sj'] =  $v['sj'];
               if($v['uip'] == '系统操作'){
                   $list[$k]['uip'] = '系统操作';
               }else{
                   $list[$k]['uip'] = '<a href="http://www.baidu.com/s?wd='.$v['uip'].'" class="dialogit" title="Ip归属地查询">'.$v['uip'].'</a>';
               }
               $list[$k]['product'] = Fun::ini()->getStatus($v['product'],['域名','其他','充值','手续费','提现','佣金提现','违约金','返利','退款']);
               $list[$k]['subtype'] = Fun::ini()->getStatus($v['subtype'],[' 一口价域名交易',' push域名',' 域名注册','转回原注册商','用户充值','后台充值','发票申请','实名认证','现金提现','其他','怀米大使','域名续费','消保店铺保证金','域名预定','域名拼团','域名竞价','域名赎回','注册域名退款','域名回收','自动委托','域名手动续费','注册包购买']);
               $list[$k]['group'] = $v['uid'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('uid'));
        return $this->view->fetch();
    }

}
