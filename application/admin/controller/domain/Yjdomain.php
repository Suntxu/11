<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use think\Config;
/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Yjdomain extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['getDomainQuality'];
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_pro_trade');
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
            if(empty($group)){ //特殊字段 不参与条件拼接
                $def = '';
            }else{
                $def = ' p.tit like "%'.$group.'%" ';
            }
            $total = $this->model
                ->alias('p')->join('domain_user n','p.userid=n.id','left')->join('storeconfig s','s.userid=p.userid','left')
                ->where($where)->where($def)
                ->count();
            $list = $this->model
                ->alias('p')->join('domain_user n','p.userid=n.id','left')->join('storeconfig s','s.userid=p.userid','left')
                ->field('p.id,p.status,p.icptrue,p.icpholder,p.type,p.icp_org,p.inserttime,p.updatetime,p.money,p.hz,p.dqsj,p.endtime,n.uid,p.tit,p.wx_check,p.qq_check,p.quality,p.special,p.api_id,p.istj,p.lock,p.hz,p.is_sift,s.flag,p.txt')
                ->where($where)->where($def) //p.webclass,
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $fun = Fun::ini();
            $zb = Config::get('quality_select');
            $apis = $this->getApis(-1);
            foreach($list as $k=>$v){
                $list[$k]['p.id'] = $v['id'];
                $list[$k]['n.uid'] = $v['uid'];
                $list[$k]['api_id'] = $apis[$v['api_id']]['tit'];
//                $list[$k]['p.webclass'] = $fun->getStatus($v['icpholder'],['无','独立','共享']);
                $list[$k]['p.icpholder'] = $fun->getStatus($v['icpholder'],['未知','阿里云','腾讯云','其他','所有']);
                $list[$k]['p.icptrue'] =    $fun->getStatus($v['icptrue'],['未知','个人','企业','未备案','存在']);
                $list[$k]['p.wx_check'] =    $fun->getStatus($v['wx_check'],['未知','未拦截','拦截']);
                $list[$k]['p.inserttime'] = $v['inserttime'];
                $list[$k]['p.icp_org'] = $v['icp_org'];
                $list[$k]['p.dqsj'] = $v['dqsj'];
                $list[$k]['p.tit'] = strtolower($v['tit']);
                $list[$k]['p.quality'] = $fun->getStatus($v['quality'],$zb);
                $list[$k]['p.type'] = $fun->getStatus($v['type'],['一口价','<span style="color:gray">满减</span>',9 => '<span style="color:orange">打包一口价</span>']);
                $list[$k]['p.status'] = $fun->getStatus($v['status'],['--','已上架','已下架']);
                $list[$k]['p.hz'] = $v['hz'];
                $list[$k]['s.flag'] = $fun->getStatus($v['flag'],['普通店铺','<span style="color:red">怀米网店铺</span>','消保店铺']);
                $list[$k]['dqsj'] = substr($v['dqsj'],0,10);
                $list[$k]['p.lock'] = $fun->getStatus($v['lock'],['<span style="color:green">正常</span>','<span style="color:red">域名被hold</span>','<span style="color:gray">发布中</span>','<span style="color:red;">域名被墙</span>','<span style="color:red">冻结中</span>']);
                // $list[$k]['p.dttype'] = $fun->getStatus($v['dttype'],[1=>'一口价','合作方一口价']);
                $list[$k]['p.txt'] = $fun->returntitdian($v['txt'],20);
            }
            $result = array("total" => $total, "rows" => $list);
            //  dump($list);exit;
            return json($result);
        }
        $requ = $this->request->get();
        $this->view->assign([
            'uid' => $this->request->get('uid'),
        ]);
        return $this->view->fetch();
    }
     /**
     * 编辑
     */
    public function show($ids){
        $ids = $this->request->get('ids');
        if($ids){
            $data = Db::name('domain_pro_trade')->alias('p')->join('domain_user u','p.userid=u.id','left')
                    ->field('p.type,p.tit,p.zcsj,p.dqsj,p.inserttime,p.endtime,p.updatetime,p.money,p.status,p.icp_serial,p.icp_org,p.display,p.wx_check,p.qq_check,u.uid,p.icpholder,p.icptrue,p.stype,p.attc,p.quality,p.lock,p.zcs,p.api_id,p.txt')
                    ->where(['p.id' => $ids])
                    ->find();
            $cates = $this->getCates();
            $data['zcs'] = $cates[$data['zcs']];
            $apiinfo= $this->getApis(-1);
            $data['api'] = $apiinfo[$data['api_id']]['tit'];

            $data['type'] =  Fun::ini()->getStatus($data['type'],['一口价域名','满减域名',9=>'打包一口价']);
            $data['status'] = Fun::ini()->getStatus($data['status'],['---','已上架','已下架']);
            $data['display'] = Fun::ini()->getStatus($data['display'],['显示','隐藏']);
            $data['inserttime'] = date('Y-m-d H:i:s',$data['inserttime']);
            $data['updatetime'] = date('Y-m-d H:i:s',$data['updatetime']);
            $data['endtime'] = date('Y-m-d H:i:s',$data['endtime']);
            // 属性信息
            $data['icpholder'] = Fun::ini()->getStatus($data['icpholder'],['未知','阿里云','腾讯云','其它','所有']);
            $data['icptrue'] = Fun::ini()->getStatus($data['icptrue'],['未知','个人','企业','未备案','存在']);
            $data['stype'] = Fun::ini()->getStatus($data['stype'],['未设置','老域名','高收录','高权重','高pr','高外链','高反链']);
            $data['attc'] = Fun::ini()->getStatus($data['attc'],['未设置','二级不死','大站','绿标']);
            // 质保 quality
            $data['quality'] = Fun::ini()->getStatus($data['quality'],['非质保','7天','30天','60天']);
            // 获取DNS 解析记录 域名模板
            $info = Fun::ini()->getWhois($data['tit']);
            $data['whois']['dns'] = empty($info['DNS']) ? '已隐藏' : $info['DNS'];
            $data['whois']['registerName'] = empty($info['联系人']) ? '已隐藏' : $info['联系人'];
            $data['whois']['registerEmail'] = empty($info['联系邮箱']) ? '已隐藏' : $info['联系邮箱'];
            // 获取 当前的解析
            $data['parse'] = Db::name('domain_record')->field('RecordId,RR,Type,Value,Line,Status,TTL')->where(['tit'=>$data['tit']])->select();
            // 交易往来
            $data['teta'] = Db::name('domain_order')->alias('o')->join('domain_user u','o.userid=u.id','left')->join('domain_user s','s.id=o.selleruserid','left')
                            ->field('u.uid,s.uid as suid,o.money,o.type,o.final_money,o.paytime')
                            ->where(['status' => 2,'tit' => $data['tit']])
                            ->select();
            $this->view->assign('data',$data);
            return $this->view->fetch();
        }else{
            $this->error('无效参数');
        }
    }
    
    // 获取质保属性
    public function getDomainQuality(){
        $arr = Config::get('quality_select');
        foreach($arr as $k => $v){
            $arr[$v] = $v;
        }
        return json($arr);
    } 

}
