<?php

namespace app\admin\controller\total\issue;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 违规取证
 */
class Illegal extends Backend
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

            $def = 'type = 0 ';
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

    //获取用户信息
    public function show(){
        //获取持有人信息
        $param = $this->request->get();

        $userid = empty($param['userid']) ? 0 : intval($param['userid']);
        $tit = empty($param['tit']) ? '' : strip_tags($param['tit']);

        if(empty($userid) || empty($tit)){
            $this->error('缺少重要参数');
        }

        //获取用户基本信息
        $userInfo = Db::name('domain_user')->field('id,uid,mot,qh,sj,uip')->where('id',$userid)->find();

        //获取持有人信息
        $authInfo = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
            ->join('domain_pro_n n','n.infoid=t.id')
            ->join('user_renzheng r','t.renzheng_id=r.id')
            ->field('t.ZhRegistrantOrganization,r.renzhengno,r.address')
            ->where('n.tit',$tit)
            ->find();

        $this->view->assign(['userInfo' => $userInfo,'authInfo' => $authInfo,'tit' => $tit]);
        return $this->view->fetch();

    }

    //添加取证域名
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
            $ftit = $this->connect->name('domain_link_show_info')->where('type',0)->whereIn('tit',array_column($domainsInfo,'tit'))->column('tit');
            if($ftit){
                $this->error('域名:'.implode(',',$ftit).'已存在');

            }
            $insert = [];
            $time = time();
            foreach($domainsInfo as $v){
                $insert[] = ['tit' => $v['tit'],'create_time' => $time,'userid' => $v['userid'],'type' => 0];
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

            $this->connect->name('domain_link_show_info')->where('type',0)->whereIn('id',$ids)->delete();

            $this->success('删除成功');

        }
    }

    //下载用户信息 用户信息 下载功能，包含：账号信息和持有人信息、登陆日志 生成wrod信息格式来，充值记录下载和解析记录下载可以生成为ecxl文档
    public function exportInfo(){
        set_time_limit(300);
        $where = $this->request->get();
        if (!is_numeric($where['userid']) || $where['userid'] < 0) {
            $this->error('请选择正确的信息');
        }
        if (empty($where['tit'])) {
            $this->error('域名信息为空');
        }
        if ($where['type'] == 1) {
            $this->rechargePHPExcel($where['userid'],$where['tit']);
        }else if($where['type'] == 2){
            $this->analysisPHPExcel($where['userid'],$where['tit']);
        }else if($where['type'] == 3){
            $this->userInfoWord($where['userid'],$where['tit']);
        }
    }
    //导出用户基本信息
    private function userInfoWord($userid,$tit){
        //获取用户基本信息
        $userInfo = Db::name('domain_user')->field('id,uid,mot,qh,sj,uip')->where('id',$userid)->find();
        //获取持有人信息
        $authInfo = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
            ->join('domain_pro_n n','n.infoid=t.id')
            ->join('user_renzheng r','t.renzheng_id=r.id')
            ->field('t.ZhRegistrantOrganization,r.renzhengno,r.address,n.tit')
            ->where('n.tit',$tit)
            ->find();
        //登录日志
        $list = Db::name('domain_loginlog')
            ->field('sj,uip,userid')
            ->where('userid',$userid)
            ->order('sj desc')
            ->select();
        foreach ($list as $k => $v){
            $list[$k]['userid'] = $userInfo['uid'];
        }
        $html = '';
        foreach ($list as $k => $v){
            $html .= '<tr>
                        <td>'.$v['sj'].'</td>
                        <td>'.$v['uip'].'</td>
                        <td>'.$v['userid'].'</td>
                     </tr>';
        }
        $content = '<h2 align="center">·平台信息</h2>
            <table border="1" align="center" style="">
                <tr>
                    <th align="center" valign="middle">登录账号：'. $userInfo['uid'] .'</th>
                </tr>
                <tr>
                    <th align="center" valign="middle">手机号：'. $userInfo['mot'] .'</th>
                </tr>
                <tr>
                    <th align="center" valign="middle">注册时间：'. $userInfo['sj'] .'</th>
                </tr>
                <tr>
                    <th align="center" valign="middle">注册IP：'. $userInfo['uip'] .'</th>
                </tr>
            </table>' .
            '<h2 align="center">·持有人基本信息</h2>
            <table border="1" align="center">
                <tr>
                    <th align="center" valign="middle">域名持有人：'. $authInfo['ZhRegistrantOrganization'] .'</th>
                </tr>
                <tr>
                    <th align="center" valign="middle">身份证地址：'. $authInfo['renzhengno'] .'</th>
                </tr>
                <tr>
                    <th align="center" valign="middle">身份证号码：'. $authInfo['address'] .'</th>
                </tr>
            </table>'
            .
            '<h2 align="center">登录日志</h2>' .
            '<table border="1" align="center">
                <tr>
                    <th>登录时间</th>
                    <th>登录IP</th>
                    <th>账户</th>
                </tr>'.$html.'
            <table>';
        //命名规则:域名_按钮名字_主键_时间
        $fileName = $authInfo['tit'] . '_' . '个人信息' . '_' .$userid . '_' . time() . '用户详细记录' . ".doc";
        if(empty($content)){
            return;
        }
        Fun::ini()->wordFile($content,$fileName);

    }

    //导出充值记录
    private function rechargePHPExcel($userid,$tit){
        //用户充值记录
        $def = ' d.ifok = 1 ';
        $list = Db::name('domain_dingdang')->alias('d')->join('domain_user u','d.userid=u.id')
            ->field('concat("`",d.ddbh) as ddbh,d.sj,d.money1,d.bz,concat("`",d.wxddbh) as wxddbh,d.uip,u.uid')
            ->where('d.userid',$userid)
            ->where($def)
            ->order('sj desc')
            ->select();
        //设置表头
        $tableheader = array('平台订单号','充值时间','充值金额','充值方式','商户订单号','IP','用户昵称');
        $filename = $tit . '_' . '充值记录' . '_' .$userid . '_' . time() . ".csv";
        Fun::ini()->csvFile($tableheader,$list,$filename);
    }

    //用户解析记录导出
    private function analysisPHPExcel($userid,$tit){
        $list = Db::name('action_record')
            ->field('remark,newstime,uip,userid,tit')
            ->where(['userid'=>$userid])
            ->order('id desc')
            ->select();
        $uid = Db::name('domain_user')->where('id',$userid)->value('uid');
        foreach($list as $k => $v){
            $list[$k]['newstime'] = date('Y-m-d H:s:i',$v['newstime']);
            $list[$k]['userid'] = $uid;
        }

        //设置表头
        $tableheader = array('记录','操作时间','IP','用户昵称','域名');
        $filename = $tit . '_' . '解析记录' . '_' . $userid . '_' . time() . ".csv";
        Fun::ini()->csvFile($tableheader,$list,$filename);

    }

}
