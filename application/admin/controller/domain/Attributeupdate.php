<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use think\Exception;
/**
 *  域名属性修改
 */
class Attributeupdate extends Backend
{

    //列表页
    public function index(){
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('domain_pro_trade_update')->alias('dptu')->join('domain_user u','u.id=dptu.userid')->join('admin a','dptu.adminid=a.id','LEFT')->where($where)->count();

            $list = Db::name('domain_pro_trade_update')
                ->alias('dptu')
                ->join('domain_user u','u.id=dptu.userid')
                ->join('admin a','dptu.adminid=a.id','LEFT')
                ->field('dptu.*,a.nickname,u.uid')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach($list as $k => $v){
                if ($v['status'] == 0) {
                    $list[$k]['manmage'] = "&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(1,{$v['id']})'>同意</a>&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(2,{$v['id']})'>拒绝</a>";
                }
                $list[$k]['dptu.status'] = $v['status'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    //同意
    public function agree(){
        $ids = $this->request->param('id');
        if(empty($ids)){
            $this->error('缺少重要参数');
        }
        $info = Db::name('domain_pro_trade_update')->field('id,tit,tradeid,userid,icpholder,icptrue,attc,baidu_sl,sogou_sl')->where('status',0)->whereIn('id',$ids)->select();

        if(empty($info)){
            $this->error('未处理域名属性申请不存在');
        }
        if ($this->request->isAjax())
        {
            $adminid = $this->auth->id;
            $time = time();
            $id = '';
            $tradeid = '';
            $icpholder = 'icpholder = CASE id';
            $icptrue = 'icptrue = CASE id';
            $attc = 'attc = CASE id';
            $baidu_sl = 'baidu_sl = CASE id';
            $sogou_sl = 'sogou_sl = CASE id';
            foreach ($info as $k => $v){
                if ($v['icpholder'] != 0) {
                    $icpholder .= ' WHEN ' . $v['tradeid'] . ' THEN ' . $v['icpholder'];
                }
                if ($v['icptrue'] != 0) {
                    $icptrue .= ' WHEN ' . $v['tradeid'] . ' THEN ' . $v['icptrue'];
                }
                if ($v['attc'] != 0) {
                    $attc .= ' WHEN ' . $v['tradeid'] . ' THEN ' . $v['attc'];
                }
                if (!empty($v['baidu_sl'])) {
                    $baidu_sl .= ' WHEN ' . $v['tradeid'] . ' THEN ' . $v['baidu_sl'];
                }
                if (!empty($v['sogou_sl'])) {
                    $sogou_sl .= ' WHEN ' . $v['tradeid'] . ' THEN ' . $v['sogou_sl'];
                }
                $id .= $v['id'] . ',';
                $tradeid .= $v['tradeid'] . ',';

            }

            if ($icpholder == 'icpholder = CASE id') {
                $icpholder = '';
            }else{
                $icpholder .= ' END, ';
            }

            if ($icptrue == 'icptrue = CASE id') {
                $icptrue = '';
            }else{
                $icptrue .= ' END, ';
            }

            if ($attc == 'attc = CASE id') {
                $attc = '';
            }else{
                $attc .= ' END, ';
            }

            if ($baidu_sl == 'baidu_sl = CASE id') {
                $baidu_sl = '';
            }else{
                $baidu_sl .= ' END, ';
            }

            if ($sogou_sl == 'sogou_sl = CASE id') {
                $sogou_sl = '';
            }else{
                $sogou_sl .= ' END, ';
            }

            $sql = "UPDATE yj_domain_pro_trade SET " . $icpholder . $icptrue . $attc . $baidu_sl . $sogou_sl;
            $sql = rtrim($sql,', ');

            $where = ' WHERE id IN (' . rtrim($tradeid, ',') . ')';
            $sql .= $where;
            $updateInfo = [
                'status' => 1,
                'time' => $time,
                'adminid' => $adminid,
            ];
            try {
                Db::startTrans();
                Db::execute($sql);
                Db::name('domain_pro_trade_update')->whereIn('id',rtrim($id,','))->update($updateInfo);
                Db::commit();
                return json(['code'=>1,'msg'=>'审核成功']);

            } catch (Exception $e) {
                //如获取到异常信息，对所有表的删、改、写操作，都会回滚至操作前的状态：
                Db::rollback();
                return ['code'=>0,'msg'=>$e->getMessage()];
            }
        }
        $titStr = '';
        $idStr = '';
        foreach ($info as $k => $v){
            $titStr .= $v['tit'] . "\n";
            $idStr .= $v['id'] . ',';
        }

        $this->view->assign(['info' => trim($titStr,"\n"),'titstr' => trim($idStr,',')]);
        return $this->view->fetch('remarks');
    }


    //拒绝
    public function refuse(){
        if ($this->request->isAjax())
        {
            $id = $this->request->post('id');
            $txt = $this->request->post('txt');
            if (empty($txt) || empty($id)) {
                $this->error('参数错误');
            }
            $info = Db::name('domain_pro_trade_update')->where('status',0)->whereIn('id',$id)->column('id');
            if(empty($info)){
                $this->error('未处理域名属性申请不存在');
            }
            $adminid = $this->auth->id;
            $updateInfo = [
                'status' => 2,
                'time' => time(),
                'adminid' => $adminid,
                'txt' => $txt,
            ];

            $res = Db::name('domain_pro_trade_update')->whereIn('id',$info)->update($updateInfo);
            if ($res) {
                return json(['code'=>1,'msg'=>'审核成功']);
            }else{
                return json(['code'=>0,'msg'=>'审核失败']);
            }
        }
    }



}