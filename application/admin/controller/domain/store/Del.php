<?php

namespace app\admin\controller\domain\store;

use app\admin\library\Redis;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
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
    protected $noNeedRight = ['checkpack','checkSelfDomain'];
    
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
            if(count($domain) > 10000){
                $this->error('一次最多可出库10000个域名');
            }
            $domains = array_chunk($domain,500);
            $domainInfo = [];
            foreach ($domains as $v){
                // 获取已有的域名
                $tempArr = $this->model->alias('p')->join('domain_user u','p.userid=u.id')->whereIn('p.tit',$v)->field('p.tit,u.uid,p.zt')->select();
                $domainInfo = array_merge($domainInfo,$tempArr);
            }

            if($domainInfo){
                $domainPro = [];
                $recycle = [];
                $pushArr = [];
                $duid = [];
                foreach($domainInfo as $v){
                    if($v['zt'] == 4){
                        $pushArr[] = $v['tit'];
                    }else if($v['zt'] == 6){
                        $recycle[] = $v['tit'];
                    }else{
                        $domainPro[] = $v['tit'];
                        $duid[] = $v['uid'];
                    }
                }
                if($pushArr){
                    $this->error('该批包含push中的域名,不可出库!'.implode(',',$pushArr));
                }
                if($recycle){
                    $this->error('该批包含回收中的域名,不可出库!'.implode(',',$recycle));
                }
                Db::startTrans();
                $domainPron = array_chunk($domainPro,500);

                foreach($domainPron as $item){
                    // 解除包
                    if($row['pack'] == 1){
                        $packData = Db::name('domain_pro_trade')->where('type',9)->whereIn('tit',$item)->field('id,pack_id')->select();
                        $packids = [];
                        foreach($packData as $v){
                            if($v['pack_id'] == 0){
                                $packids[] = $v['id'];
                            }else{
                                $packids[] = $v['pack_id'];
                            }
                        }
                        if($packids){
                            
                            $packids = array_unique($packids);
                            // 查下域名的ID
                            $domainId = Db::name('domain_pro_trade')->whereIn('id|pack_id',$packids)->column('did');

                            Db::name('domain_pro_trade')->whereIn('id|pack_id',$packids)->delete();
                            // 改变主表状态
                            Db::name('domain_pro_n')->whereIn('id',$domainId)->setField('zt',9);
                        }
                        
                    }
                    $this->model->whereIn('tit',$item)->delete();
                    // 删除购物车表 和  订单表
                    Db::name('domain_cart')->whereIn('tit',$item)->delete();
                    Db::name('domain_follow')->whereIn('tit',$item)->delete();
                    Db::name('domain_order')->where('status != 1')->whereIn('tit',$item)->delete();
                    // 删除解析日志
                    Db::name('domain_record')->whereIn('tit',$item)->delete();
                    // 删除一口价
                    Db::name('domain_pro_trade')->whereIn('tit',$item)->delete();
                }

                array_walk($domainPro,'self::waikArr',$duid); //把用户名用^拼接到域名里面
                // 插入记录
                Db::name('domain_operate_record')->insert(['create_time'=>time(),'tit'=>implode(',',$domainPro),'operator_id'=>$this->auth->id,'value' => '出库']);
                Db::commit();

                //暂时排除打包域名
                //$syncInfo = Db::name('domain_pro_trade')->whereIn('tit',$domainPro)->whereIn('type',[0,1])->column('tit');
//                    if($syncInfo){
//                        $redis = new Redis(['select' => 3]);
//                        $key = 'delete_admin_sync_agent_data'.time().rand(1111,9999);
//                        $redis->RPush('sync_agent_domain',$key);
//                        $redis->hmset($key,$syncInfo);
//                    }

                $this->success('操作成功','reload');
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
        $a = array_values(Fun::ini()->moreRow($domain));
        
        $pack = Db::name('domain_pro_trade')->where(['type'=>9])->whereIn('tit',$a)->column('id');
        if($pack){
            $msg = 'pack';
        }else{
            $msg = 'ok';
        }
        return json(['code'=>0,'msg'=>$msg,'data'=>$a]);
    }
    // 检测是否含有自己账户域名 
    public function checkSelfDomain(){
        $domain = $this->request->post('domain','');
        if(empty($domain)){
           return json(['code'=>1,'msg'=>'请输入域名']);
        }
        $a = Fun::ini()->moreRow($domain);
        //获取userid
        $userids = Db::name('domain_user')->whereIn('uid',\think\Config::get('self_username'))->column('id');
        if(empty($userids)){
            return json(['code' => 1,'msg' => 'admin/config.php用户名设置有误!']);
        }
        $self = Db::name('domain_pro_n')->where('userid','not in',$userids)->whereIn('tit',$a)->column('tit');
        return json(['code'=>0,'msg'=>'ok','self'=>$self]);
    }

    /**
     * 组合数组
     */
    public static function waikArr(&$value,$key,$lts){
        return $value = $value.'^'.$lts[$key];
    }

}
