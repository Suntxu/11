<?php

namespace app\admin\controller\oprecord;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 一口价域名修改记录
 *
 * @icon fa fa-user
 */
class Domainmodi extends Backend
{
    protected $noNeedRight = ['show'];
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        global $remodi_db;
        parent::_initialize();
        $this->model = Db::connect($remodi_db)->name('domain_admin_record');
    }
    
    /**
     * 查看
     */
    public function index(){
        if ($this->request->isAjax()){
            
            list($where, $sort, $order, $offset, $limit,$admin,$uid) = $this->buildparams();
            
            $def = ' 1 = 1 ';

            $aInfo = $this->getAdminNickname();
           
            if($admin){
                $admin_id = isset($aInfo[$admin]) ? $aInfo[$admin] : 0;
                $def .= ' and admin_id = '.$admin_id;
            }
            if($uid){
                $userid = Db::name('domain_user')->where('uid',$uid)->value('id');
                $def .= ' and userid = '.$userid;
            }
            $total = $this->model->where($def)->where($where)->count();
            $list = $this->model->field('id,time,type,userid,admin_id,tit,ext')
                    ->where($def)->where($where)
                    ->order($sort,$order)
                    ->limit($offset,$limit)
                    ->select();

            $aInfo = array_flip($aInfo);
            
            $fun = Fun::ini();
            foreach($list as &$v){

                if($v['type']  == 8 && json_decode($v['ext'],true) ){

                    $v['ext'] = "<a href='javascript:;' data-id={$v['id']} onclick='showDetail(this)' data-operate='ext' data-url='/admin/oprecord/domainmodi/show'>查看包内域名</a>";;
                }

                $v['show'] = "<a href='javascript:;' data-id={$v['id']} onclick='showDetail(this)' data-operate='old_value,new_value' data-url='/admin/oprecord/domainmodi/show'>详情</a>";

                $v['type'] = $fun->getStatus($v['type'],['域名出库','冻结操作','修改微信cookie','手动补单','域名入库','注册域名退款','修改一口价属性','手动过户','修改一口价价格']);
                
                $v['group'] = isset($aInfo[$v['admin_id']]) ? $aInfo[$v['admin_id']] : '--';
                if(empty($userid)){
                    $v['special_condition'] = Db::name('domain_user')->where('id',$v['userid'])->value('uid');
                }else{
                    $v['special_condition'] = $uid;
                }



            }
            return json(['total'=>$total,'rows'=>$list]);
        }
        return $this->view->fetch();
    }


    /**
     * 查看值
     */
    public function show(){

        if($this->request->isAjax()){
            
            $param = $this->request->post();

            if(empty($param['id']) || empty($param['field']) || !in_array($param['field'], ['ext','old_value,new_value'])){
                return ['code' => 1,'msg' => '缺少重要参数'];                
            }
            
            $data = $this->model->where('id',$param['id'])->field($param['field'])->find();
            if(empty($data)){
                return ['code' => 1,'msg' => '数据有误'];   
            }
            
            $fis = explode(',',$param['field']);
            foreach($fis as $v){
                $data[$v] = json_decode($data[$v],true);
            }
                
            return ['code' => 0,'msg'  => 'success','data' =>  $data];

        }   

    }



}
