<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 参与域名或者店铺
 *
 * @icon fa fa-user
 */
class Participator extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

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
        $this ->request->filter('strip_tags');
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('p')
                ->join('domain_user u','p.userid = u.id','left')
                ->where($where)
                ->where('p.type = 1 and status = 1')
                ->count();
            $list = $this->model
                ->alias('p')
                ->join('domain_user u','p.userid = u.id','left')
                ->field('p.id,p.tit,p.money,u.uid,p.inserttime,p.type')
                ->where($where)
                ->where('p.type = 1 and status = 1')
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            foreach($list as $k=>$v){
                $list[$k]['type'] = '满减';
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
                $time = time();
                // 域名列表
                if($params['type'] == 1){
                    if(empty($params['tit'])){
                        $this->error('请选择填写域名');
                    }
                    $a = Fun::ini()->moreRow($params['tit']);
                    $domain = Db::name('domain_pro_trade')->where(['type'=>0,'status'=>1])->whereIn('tit',$a)->column('did');
                    if(!$domain){
                        $this->error('请输入一口价在售域名');
                    }
                    Db::name('domain_pro_trade')->whereIn('did',$domain)->update(['inserttime'=>$time,'type'=>$params['ac_coupon']]);
                }else{
                    $params['userid'] = Db::name('storeconfig')->alias('s')->join('domain_user u','u.id=s.userid','left')->where('u.uid',$params['userid'])->value('u.id');
                    if(empty($params['userid'])){
                        $this->error('填写的用户不存在或者尚未开通店铺');
                    }
                    Db::name('domain_user')->where(['id'=>$params['userid']])->update(['ac_coupon'=>$params['ac_coupon']]);
                    // Db::name('domain_pro_trade')->where(['userid'=>$params['userid']])->update(['type' => 1]);
                }
                $this -> success('添加成功');       
            }
        }
        // 获取的域名列表
        $id = $this->request->get('id','0');
        $data = Db::name('domain_pro_trade')->whereIn('id',$id)->column('tit');
        // 活动类型
        $arr = [1=>'红包满减'];
        $this->view->assign(['type'=>$arr,'data'=>$data]);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            Db::name('domain_pro_trade')->whereIn('id',$ids)->update(['type'=>0,'inserttime'=>time()]);
            $this -> success('删除成功');
       }else{
            $this -> error('缺少重要参数');
       }
    }
    /**
     * 按用户名删除参与域名
    */
    public function delshop(){
        $uid = $this->request->post('uid','');
        if($uid == ''){
            return json(['code'=>1,'msg'=>'用户名不能为空']);
        }
        $user = Db::name('domain_user')->field('id')->where(['uid'=>trim($uid)])->find();
        if(empty($user['id'])){
            return json(['code'=>1,'msg'=>'用户名不正确']);
        }
        Db::name('domain_user')->where(['id'=>$user['id']])->update(['ac_coupon'=>0]);
        Db::name('domain_pro_trade')->where(['userid'=>$user['id'],'type'=>1])->update(['type'=>0,'inserttime'=>time()]);
        return json(['code'=>0,'msg'=>'操作成功']);
    }

}
