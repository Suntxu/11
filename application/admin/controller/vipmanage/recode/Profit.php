<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 平台盈利记录
 *
 * @icon fa fa-user
 */
class Profit extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    { 
        global $remodi_db;
        parent::_initialize();
        $this->model = Db::connect($remodi_db)->name('platform_profit');
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
              $userids = Db::name('domain_user')->where('uid',trim($uid))->column('id');
              if($userids){
                $def = ' userid in('.implode(',', $userids).') ';
              }else{
                $def = ' userid = 0 ';
              }
            }

            $total = $this->model->where($where)->where($def)->count();

            $list = $this->model->field('type,infoid,subtype,create_time,uip,money,remark,userid')
                    ->where($where)->where($def)->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            //计算金额
            $ztotal = sprintf('%.2f',$this->model->where($where)->where($def)->sum('money'));

            $fun = Fun::ini();
            foreach($list as &$v){
                // 拼链接
               switch ($v['subtype']) {
                    case 0:
                        $show = '/admin/domain/autoentrust?id='.$v['infoid'];
                       break;
                   default:
                       $show = '';
                       break;
               }
               if($show){
                    $v['showurl'] = '<a href="'.$show.'" class="dialogit"  title="详情">详情</a>';
               }else{
                    $v['showurl'] = '无连接';
               }
               $v['ztotal'] = $ztotal;
               if($uid){

                $v['group'] = $uid;

               }else{

                $v['group'] = Db::name('domain_user')->where(['id' => $v['userid']])->value('uid');

               }
               $v['type'] = $fun->getStatus($v['type'],['域名']);
               $v['subtype'] = $fun->getStatus($v['subtype'],['自动委托购买']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

}
