<?php

namespace app\admin\controller\total\issue;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 域名解析排查
 */
class Parsecheck extends Backend
{
    private $connect = null;

    public function _initialize()
    {
        global $remodi_db;
        $this->connect = Db::connect($remodi_db);
        parent::_initialize();
    }

    //查看列表
    public function index(){

        if($this->request->isAjax()){

            list($where, $sort, $order, $offset, $limit,$uid) = $this->buildparams();

            $def = 'type = 1 ';
            if($uid){
                $uid = trim($uid);
                $userid = Db::name('domain_user')->where('uid',$uid)->value('id');
                $def .= ' and userid = '.($userid ? $userid : 0);
            }
            $total = $this->connect->name('domain_link_show_info')->where($where)->where($def)->count();

            $list = $this->connect->name('domain_link_show_info')
                ->field('id,tit,create_time,userid')
                ->where($where)->where($def)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();

            if(empty($userid)){
                $userids = array_unique(array_column($list,'userid'));
                $uinfo = Db::name('domain_user')->whereIn('id',$userids)->column('uid','id');
            }

            foreach($list as &$v){
                if(empty($userid)){
                    $v['group'] = isset($uinfo[$v['userid']]) ? $uinfo[$v['userid']] : '--';
                }else{
                    $v['group'] = $uid;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    //获取域名信息
    public function show(){
        //获取持有人信息
        $param = $this->request->get();

        $userid = empty($param['userid']) ? 0 : intval($param['userid']);
        $tit = empty($param['tit']) ? '' : strip_tags($param['tit']);
        $uid = empty($param['uid']) ? '' : strip_tags($param['uid']);

        if(empty($userid) || empty($uid) || empty($tit)){
            $this->error('缺少重要参数');
        }

        //查询域名
        $data = Db::name('domain_pro_n')->field('tit,inserttime,zcsj,dqsj,len,zt,status,pushid,zcs,api_id')->where(['userid' => $userid,'tit' => $tit])->find();
        if($data){
            $cates = $this->getCates();
            $data['zcs'] = $cates[$data['zcs']];
            $apiinfo= $this->getApis(-1);
            $data['api'] = $apiinfo[$data['api_id']]['tit'];
            $data['zt'] =  Fun::ini()->getStatus($data['zt'],['--','<span style="color:blue">发布一口价</span>','<span style="color:blue;">打包一口价</span>',4=>'<span style="color:blue;">push域名中</span>',5=>'<span style="color:gray;">转回原注册商</span>',9=>'<span style="color:green;">正常状态</span>']);
            $data['status'] = Fun::ini()->getStatus($data['status'],['<span style="color:green">正常</span>',1=>'<span style="color:red">域名被hold</span>',4=>'<span style="color:red">冻结中</span>']);
            $data['inserttime'] = date('Y-m-d H:i:s',$data['inserttime']);
        }

        $this->view->assign(['data' => $data,'uid' => $uid,'tit' => $tit]);
        return $this->view->fetch();

    }

    //添加域名
    public function add()
    {
        if ($this->request->isPost()) {
            $domain = $this->request->post('domains');
            if(empty($domain)){
                $this->error('请输入域名参数');
            }
            $domains = Fun::ini()->moreRow($domain);
            if(count($domains) > 500){
                $this->error('最多可输入500个域名');
            }

            $domainsInfo = Db::name('domain_pro_n')->field('userid,tit')->whereIn('tit',$domains)->select();
            if(empty($domainsInfo)){
                $this->error('请输入域名库存在的域名');
            }
            $ftit = $this->connect->name('domain_link_show_info')->where('type',1)->whereIn('tit',array_column($domainsInfo,'tit'))->column('tit');
            if($ftit){
                $this->error('域名:'.implode(',',$ftit).'已存在');

            }
            $insert = [];
            $time = time();
            foreach($domainsInfo as $v){
                $insert[] = ['tit' => $v['tit'],'create_time' => $time,'userid' => $v['userid'],'type' => 1];
            }

            $this->connect->name('domain_link_show_info')->insertAll($insert);

            $this->success('添加成功');

        }
        return $this->view->fetch();
    }

    public function del($ids=null){
        if($this->request->isAjax()){
            if(empty($ids)){
                $this->error('缺少重要参数');
            }

            $this->connect->name('domain_link_show_info')->where('type',1)->whereIn('id',$ids)->delete();

            $this->success('删除成功');

        }
    }


}
