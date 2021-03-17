<?php

namespace app\admin\controller\domain\store;

use app\common\controller\Backend;
use think\Db;

/**
 * 域名出库
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class Del extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    protected $model = null;
    protected $noNeedRight = ['checkpack'];
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_pro_n');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post("row/a");
            if(!$row['ttxt']){
                $this->error('请输入要出库的域名');
            }
            $domain = array_filter(explode(',',$row['ttxt']));
            // 获取已有的域名
            $domainPro = $this->model->whereIn('tit',$domain)->column('tit');
            if($domainPro){
                Db::startTrans();
                // 解除包
                if($row['pack']){
                    // 查下域名的ID
                    $domainId = Db::name('domain_pro_trade')->where('id in ( '.$row['pack'].' ) or pack_id in ('.$row['pack'].')' )->column('did');
                    // 删除包域名
                    Db::name('domain_pro_trade')->where('id in ( '.$row['pack'].' ) or pack_id in ('.$row['pack'].')' )->delete();
                    // 改变主表状态
                    Db::name('domain_pro_n')->whereIn('id',$domainId)->setField('zt',9);
                }
                //删除域名 
                $del = $this->model->whereIn('tit',$domainPro)->delete();
                if($del){
                    // 插入记录
                    Db::name('domain_operate_record')->insert(['create_time'=>time(),'tit'=>implode(',',$domainPro),'operator_id'=>$this->auth->id,'value' => '出库']);
                    // 删除购物车表 和  订单表
                    Db::name('domain_cart')->whereIn('tit',$domainPro)->delete();
                    Db::name('domain_order')->where('status != 1')->whereIn('tit',$domainPro)->delete();
                    // 删除解析日志
                    Db::name('domain_record')->whereIn('tit',$domainPro)->delete();
                    // 删除一口价
                    Db::name('domain_pro_trade')->whereIn('tit',$domainPro)->delete();
                    Db::commit();
                    $this->success('操作成功','reload');
                }else{
                    Db::rollback();
                    $this->error('事务提交失败,请重新操作！','reload');
                }
            }
            $this->error('请输入数据库存在的域名','reload');
        }
        //批量出库域名列表
        $id = $this->request->get('id',0);
        $domain = $this->model->field('tit')->where('id','in',$id)->select();
        $this->view->assign('domain',$domain);
        return $this->view->fetch();
    }
    // 检测域名
    public function checkpack(){
        $domain = $this->request->post('domain','');
        if(empty($domain)){
           return json(['code'=>1,'msg'=>'请输入域名']);
        }
        $av = str_replace("\r","",$domain);
        $a = preg_split("/\n/",$av);
        $a = array_filter(array_unique($a));
        $pack = Db::name('domain_pro_trade')->where(['type'=>9])->whereIn('tit',$a)->column('id');
        if($pack){
            $msg = 'pack';
        }else{
            $msg = 'ok';
        }
        return json(['code'=>0,'msg'=>$msg,'data'=>$a,'pack'=>$pack]);
    }

    // 检测是否含有自己账户域名 
    public function checkSelfDomain(){
        $domain = $this->request->post('domain','');
        if(empty($domain)){
           return json(['code'=>1,'msg'=>'请输入域名']);
        }
        $av = str_replace("\r","",$domain);
        $a = preg_split("/\n/",$av);
        $a = array_filter(array_unique($a));
        if(count($a) > 3000){
             return json(['code'=>1,'msg'=>'请输入域名']);
        }
        //获取userid
        $userids = Db::name('domain_user')->whereIn('uid',\think\Config::get('self_username'))->column('id');
        if(empty($userids)){
            return json(['code' => 1,'msg' => 'admin/config.php用户名设置有误!']);
        }
        $self = Db::name('domain_pro_n')->whereIn('userid',$userids)->whereIn('tit',$a)->column('tit');
       
        return json(['code'=>0,'msg'=>'ok','self'=>$self]);
    }

}
