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
class Salelog extends Backend
{
    protected $noNeedRight = ['show'];
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_pro_trade_history');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->alias('c')->join('domain_user u','c.userid=u.id','left')->join('domain_user s','c.selleruserid=s.id','left')->where($where)->count();
            $list = $this->model->alias('c')->join('domain_user u','c.userid=u.id','left')->join('domain_user s','c.selleruserid=s.id','left')->field('u.uid as uuid,u.id,s.uid as suid,c.id,c.tit,c.type,c.zcs,c.create_time,c.batch,c.is_sift,c.hz,c.len,c.wx_check,c.qq_check,c.icpholder,c.icptrue,c.special,c.stype,c.istj,c.money,c.attc,c.api_id,c.txt,c.id as cid,c.agent_cost')
                ->where($where)->order($sort,$order)->limit($offset, $limit)
                ->select();

            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money) as n FROM '.PREFIX.'domain_pro_trade_history ';
            }else{
                $conm = 'SELECT sum(c.money) as n  FROM '.PREFIX.'domain_pro_trade_history as c LEFT JOIN '.PREFIX.'domain_user as u ON c.userid=u.id LEFT JOIN '.PREFIX.'domain_user as s ON c.selleruserid=s.id '.$sql;
            }
            $res = Db::query($conm);
            $fun = Fun::ini();
            $apis = $this->getApis(-1);
            $zcss = $this->getCates();
            foreach($list as $k => $v){
                if($v['type'] == 9){
                    $list[$k]['tit'] .= '<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showPack('.$v['cid'].')" >查看更多</span>';
                }
                $list[$k]['c.type'] = $fun->getStatus($v['type'],['正常订单','满减订单','微信活动订单',9=>'打包域名订单']);
                $list[$k]['wx_check'] = $fun->getStatus($v['wx_check'],['未知','未拦截','已拦截']);
                $list[$k]['qq_check'] = $fun->getStatus($v['qq_check'],['未知','未拦截','已拦截']);
                
                $list[$k]['icpholder'] = $fun->getStatus($v['icpholder'],['未知','阿里云','腾讯云','其它','所有']);
                $list[$k]['icptrue'] = $fun->getStatus($v['icptrue'],['未知','个人','企业','未备案','存在']);
               
                $list[$k]['stype'] = $fun->getStatus($v['stype'],['未设置','老域名','高收录','高权重','高pr','高外链','高反链']);
                $list[$k]['istj'] = $fun->getStatus($v['istj'],['默认','--','推荐']);
                $list[$k]['attc'] = $fun->getStatus($v['attc'],['未设置','二级不死','大站','绿标']);

                $list[$k]['u.uid'] = $v['uuid']; 
                $list[$k]['s.uid'] = $v['suid']; 
                $list[$k]['c.create_time'] = $v['create_time'];
                $list[$k]['zje'] = $res[0]['n'];
                $list[$k]['api_id'] =  empty($apis[$v['api_id']]) ? '--' : $apis[$v['api_id']]['tit'];
                $list[$k]['zcs'] = $zcss[$v['zcs']];
                $list[$k]['vtxt'] = '<span style="cursor:pointer;color:#0066FF;text-decoration:underline;" id="remark'.$v['cid'].'" >查看</a>';
                if($v['agent_cost'] != 0){
                    $list[$k]['money'] = $v['money'].'<br><span style="color:red;font-size:8px;">卖出'.$v['agent_cost'].'</span>';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 查看详情
     */
    public function show(){

        if($this->request->isAjax()){
            $id = $this->request->post('id');
            if(empty($id)){
                return ['code' => 1,'msg' => '缺少重要参数'];
            }
            $tits = $this->model->where('id',$id)->value('pack');
            return ['code' => 0,'msg' => 'success','data' => $tits];

        }

    }

}
