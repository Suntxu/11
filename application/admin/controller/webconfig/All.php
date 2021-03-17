<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Exception;
use think\Db;
use app\admin\library\Redis;
use app\admin\common\Fun;

/**
 * 系统配置
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class all extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    protected $model = null;
    protected $noNeedLogin = ['index'];
    
    protected $d2 = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_control');
        global $db2;
        $this -> d2 = Db::connect($db2)->name('config');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {
       
        $siteList = $this->model->alias('c1')->join('domain_config c2','c1.id=c2.id','left')->join('activity_page_config c3','c1.id=c3.id')
            ->field('c1.*,c2.ali_rebate_unify_a,c2.ali_rebate_unify_b,c2.domain_shift_max_limit,c2.exclusive_material_userid,c3.*')
            ->where('c1.id',1)
            ->find();
        $siteList['domain_show'] = $siteList['domain_show'] ? explode('@.@',$siteList['domain_show']) : array('','','');
        $siteList['domain_info'] = $siteList['domain_info'] ? explode('@.@',$siteList['domain_info']) : array('','','');
        $siteList['mail'] = $siteList['mailtxt'] ? explode(',',$siteList['mailtxt']) : array('','','','');
        $siteList['qqapp'] = $siteList['qqapp'] ? explode(',',$siteList['qqapp']) :  array('','');
        $siteList['alipay'] = $siteList['alipay'] ? explode(',',$siteList['alipay']) :  array('','','');
        $siteList['tenpay'] = $siteList['tenpay'] ? explode(',',$siteList['tenpay']) :  array('','');
        $siteList['wxpay'] = $siteList['wxpay'] ? explode(',',$siteList['wxpay']) :  array('','','','');
        $siteList['active'] = true;
        if($siteList['exclusive_material_userid']){
            $uids = Db::name('domain_user')->whereIn('id',$siteList['exclusive_material_userid'])->column('uid');
            $siteList['exclusive_material_userid'] = implode("\n",$uids);
        }
        $redis = new Redis();
        $this->view->assign('checkdomain_use_dm',$redis->get('checkdomain_use_dm'));
        $this->view->assign('siteList', $siteList);
        $data = $this -> d2 -> find(1);
        $this->view->assign('db2', $data);
        return $this->view->fetch();
    }
    /**
     * 编辑
     * @param null $ids
     */
    public function edit($flag = NULL)
    {   
        if ($this->request->isPost()) {
            $param = $this->request->post();
            $row = empty($param['row']) ? [] : $param['row'];
            if(array_key_exists ('checkdomain_use_dm',$row)){
                $redis  = new Redis();
                $redis->set('checkdomain_use_dm',$row['checkdomain_use_dm']);
                unset($row['checkdomain_use_dm']);
            }
            if($flag == 'qjsz'){//全局设置
                $row['domain_info'] = $param['domain_info_tit'].'@.@'.$param['domain_info_keyword'].'@.@'.$param['domain_info_desc'];
                $row['domain_show'] = $param['domain_show_tit'].'@.@'.$param['domain_show_keyword'].'@.@'.$param['domain_show_desc'];
            }elseif($flag == 'qxpz'){//通信配置
                $row['mailtxt'] = $param['m1'].','.$param['m2'].','.$param['m3'].','.$param['m4'];
                $row['qqapp'] = $param['q1'].','.$param['q2'];
            }elseif($flag == 'zfjk'){
                $row['alipay'] = $param['zf1'].','.$param['zf2'].','.$param['zf3'];
                $row['tenpay'] = $param['tenpay1'].','.$param['tenpay2'];
                $row['wxpay'] = $param['wxpay0'].','.$param['wxpay1'].','.$param['wxpay2'].','.$param['wxpay3'];
            }
            if ($row) {
                if($flag == 'seosz'){
                    // 插入后台操作记录
                     Db::name('domain_operate_record')->insert(['create_time'=>time(),'tit'=>'','operator_id'=>$this->auth->id,'type'=>2,'value'=>$row['wx_cookie']]);
                    $this -> d2 -> where(['id'=>1]) -> update($row);
                }elseif($flag == 'rebate'){
                    
                    //插入第二个配置表
                    $con = $this->request->post('con/a');
                    if(isset($row['exclusive_material_userid'])){
                        $userids = Db::name('domain_user')->whereIn('uid',Fun::ini()->moreRow($row['exclusive_material_userid']))->column('id');
                        $row['exclusive_material_userid'] = implode(',', $userids);
                    }
                    Db::name('domain_config')->where(['id'=>1])->update($row);
                    if($con){
                        $this->model->where(['id'=>1])->update($con);
                    }
                }elseif($flag == 'activity'){
                    //活动配置表
                    Db::name('activity_page_config')->where(['id'=>1])->update($row);

                }else{
                    $this->model->where(['id'=>1])->update($row);
                }
                $this->success('修改成功');
            }else{
                $this->error(__('Parameter %s can not be empty', ''));
            }
            
        }
    }


}
