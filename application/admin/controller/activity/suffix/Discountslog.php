<?php

namespace app\admin\controller\activity\suffix;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 注册后缀优惠注册记录
 *
 * @icon fa fa-user
 */
class Discountslog extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('suffix_discounts_log');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {  

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->alias('s')->join('suffix_discounts_config c','s.sid=c.id')->join('domain_user u','u.id=s.userid')
                ->where($where)
                ->count();

            $list = $this->model->alias('s')->join('suffix_discounts_config c','s.sid=c.id')->join('domain_user u','
                u.id=s.userid')
                ->field('u.uid,c.hz,c.etime,c.money,c.stime,c.num,s.ctime,s.num as snum,s.ynum')
                ->where($where)->order($sort,$order)->limit($offset, $limit)
                ->select();

            $fun = Fun::ini();
            foreach($list as &$v){
                $v['s.status'] = $fun->getStatus($v['status'],['正常','禁止']);
                $v['niun'] = '<font color="red">'.$v['ynum'].'</font>/<font color="orange">'.$v['snum'].'</font>';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        
        if ($this->request->isPost()){
            $params = $this->request->post("row/a"); 
            if ($params){
                if(empty($params['sid']) || empty($params['uid']) || empty($params['num']) ){
                    $this->error('请填写必填参数');
                }
                $surplus = $this->getDiscounts($params['sid']);
                if($surplus < $params['num']){
                    $this->error('已超过所剩优惠数量');
                }
                $params['userid'] = $this->judgeQualification();
                $params['ctime'] = time();
                $this->model->insert($params);
                $this -> success('添加成功');       
            }
        }
        $data = $this->getDiscounts();
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a"); 
            if ($params){
                if(empty($params['sid']) || empty($params['uid']) || empty($params['num']) ){
                    $this->error('请填写必填参数');
                }

                $surplus = $this->getDiscounts($params['sid']);
                if($surplus < $params['num']){
                    $this->error('已超过所剩优惠数量');
                }
                $this->model->update($params);
                $this -> success('修改成功');       
            }
        }
        $disc = $this->getDiscounts();
        $data = $this->model->alias('s')->join('domain_user u','u.id=s.userid')->field('u.uid,s.sid,s.num,s.ynum,s.status')->where('s.id',$ids)->find();
        $this->view->assign(['data' => $data,'disc' => $disc]);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this->model->delete($ids);
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }
    /**
     * 获取可用优惠
     */
    private function getDiscounts($id = null){
        $time = time();
        if($id){
            //优惠剩余数量
            $data = Db::name('suffix_discounts_config')->alias('c')->join('suffix_discounts_log l','c.id=l.sid','left')
                ->field('sum(c.num) as ztoal,sum(l.num) as total')
                ->where(' c.status = 0 and c.stime < '.$time.' and c.etime > '.$time.' and c.id = '.$id)
                ->find();
                return intval($data['ztoal'] - $data['total']);
        }else{
            //获取可使用优惠后缀
            $data = Db::name('suffix_discounts_config')->alias('c')->join('suffix_discounts_log l','c.id=l.sid','left')
                ->field('sum(c.num) as ztoal,sum(l.num) as total,c.hz,c.id')
                ->where(' c.status = 0 and c.stime < '.$time.' and c.etime > '.$time)
                ->group('c.id')
                ->select();
            foreach($data as &$v){
                if($v['total'] == $v['ztoal']){
                    unset($v);
                }
            }
        }
        
        return $data;
    }
    /**
     * 判断用户是否合格
     */
    public function judgeQualification($uid,$sid){
        // 判断用户是否存在
        $data = Db::name('domain_user')->alias('u')->join('user_renzheng r','r.userid=u.id')
            ->field('u.id,u.mot,r.renzhengno')->where(['u.uid' => $uid,'r.status' => 2])
            ->select();
        // $data = Db::name('domain_user')->field('id,mot')->where(['uid' => $uid])->find();
        if(empty($data)){
            $this->error('该用户不存在或未实名认证');
        }

        $renzhengnos = array_column($data, 'renzhengno');
        $users = $data[0];

        if($users['zt'] != 1){
            $statusmsg = Fun::ini()->getStatus($users['zt'],['状态不正确','','邮箱未激活','已被禁用','安全码错误过多']);
            $this->error('该账户'.$statusmsg);
        }
        //获取手机号相同并且证件号相同的账户userid
        $userids = Db::name('domain_user')->alias('u')->join('user_renzheng r','r.userid=u.id')
            ->where(['u.zt' => 1,'u.mot' => $users['mot'] ])->whereIn('r.renzhengno',$renzhengnos)
            ->column('u.id');
        //查询这些userid是否已经有过优惠
        $flag = Db::name('suffix_discounts_log')->where(['sid' => $sid])->whereIn('userid',$userids)->value('id');
        if($flag){
            $this->error('该账户(同一手机号/身份证其他账户)已经存在优惠记录!');
        }
        return $users['id'];
    }
}
