<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 系统配置
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class Editdomain extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_pro_trade');
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
                $this->error('请输入要修改的域名');
            }
            if(isset($row['txt']) && mb_strlen($row['txt']) > 50 ){
                $this->error('域名简介最多50个字符');
            }
            if($row['money'] < 0 ){
                $this->error('出售价格不能设置小于0的值');
            }
            $a = Fun::ini()->moreRow($row['ttxt']);
            if(count($a) > 5000){
                $this->error('最多可修改5000个域名');
            }
            unset($row['ttxt']);
            // 查找域名是否存在
            if(isset($row['status']) || isset($row['money']) || $row['operate'] == 1){
                $flag = true;
            }else{
                $flag = false;
            }
            list($domainPro,$userids,$did) = $this->getDomains($a,$flag);
            // 查看要重置的属性
            $attr = $this->request->post('flag/a');
            if($domainPro){
                foreach($row as $k => $v){
                    if(empty($v)){
                        unset($row[$k]);
                    }
                }
                if(empty($row) && empty($attr[0])){
                    $this->error('请选择要修改的项');
                }

                //限制同一个用户的域名
                if(isset($row['status']) || isset($row['money']) || isset($row['operate'])){
                    if(count($userids) > 1){
                        $this->error('上下架、修改出售价格、删除在售域名操作需要域名同归属一个用户');
                    }
                    //锁定用户
                    Fun::ini()->lockKey($userids[0].'_domain_transaction',10) || $this->error('前台正在对某个域名操作,请10秒后再试');
                    if(isset($row['operate']) && $row['operate'] == 1 ){ //直接转向删除一口价
                        $this->delSaleDomain($did,$userids[0]);
                    }
                }
                // 重置的归0
                if(!empty($attr[0])){
                    $attr = array_map(function($v){ return 0; },array_flip($attr));
                    if(isset($attr['txt'])){
                        $attr['txt'] = '';
                    }
                    $row = array_merge($row,$attr);
                }

                $logmsg = $this->parseParam($row);
                $domainChunk = array_chunk($domainPro, 1000);
                Db::startTrans();

                Db::name('domain_operate_record')->insert([
                    'tit' => implode(',', $domainPro),
                    'operator_id' => $this->auth->id,
                    'create_time' => time(),
                    'type' => 6,
                    'value' => $logmsg,
                ]);


                foreach($domainChunk as $v){
                    $this->model->whereIn('tit',$v)->update($row);
                }

                Db::commit();

                if(isset($row['status']) || isset($row['money'])){
                    //锁定用户
                    Fun::ini()->unlockKey($userids[0].'_domain_transaction');

                }

                $this->success('操作成功','reload');  
            }else{
                $this->error('请选择出售中的域名');
            }
        }
        //批量修改域名列表
        $id = $this->request->get('id',0);
        $domain = $this->model->field('tit')->where('id','in',$id)->select();
        $this->view->assign('domain',$domain);
        return $this->view->fetch();
    }
    /**
     * 批量更新时间
     */
    public function BtachUpdate(){
        $id = empty($this->request->post('id/a'))?'':$this->request->post('id/a');
        if($id){
            //时间更新
            $this->model->whereIn('id',$id)->update(['updatetime'=>time()]);
            echo '操作成功';
            die;
        }else{
            echo '参数有误';
            die;
        }
    }


    // /**
    //  * 存入redis 并同步分销系统
    //  */
    // private function aysnAgentData($tits,$param){

    //     $data = [];
    //     foreach($tits as $k => $v){
    //         $param['tit'] = $v;
    //         $data[] = $param;

    //     }

    //     $reids = new Redis(['select' => 3]);
    //     $key = time() . 'admin'. rand(1111, 9999) . '_sync_agent_update';
    //     $redis->LPush('sync_agent_domain', $key);
    //     $redis->set($key, json_encode($data));

    // }


    /**
     * 解析各项配置
     */
    private function parseParam($data){
        $str = '';
        $reset = '';
        $quality = \think\Config::get('quality_select');
        $quality[0] = '';
        $arr = [
            'wx_check' => ['微信检测',['','未拦截','拦截']],
            'qq_check' => ['qq检测',['','未拦截','拦截']],
            'quality' => ['质保域名',$quality],
            'special' => ['特价域名',['','是','否']],
            'icpholder' => ['建站类型',['','阿里云','腾讯云','其它','所有']],
            'icptrue' => ['建站性质',['','个人','企业','未备案','存在']],
            'attc' => ['特殊属性',['','二级不死','大站','绿标']],
            'stype' => ['域名类型',['','老域名','高收录','高权重','高pr','高外链','高反链']],
            'is_sift' => ['精选域名',['','设置']],
            'istj' => ['推荐域名',['',2 =>'设置']],
            'status' => ['上下架',['','上架','下架']],
            'txt' => ['域名简介',['']],
            'money' => ['出售价格',['']],
            'lock' => ['域名状态',['','域名hold']],
        ];
        
        foreach($data as $k => $v){
            if(empty($v)){
                $reset .= $arr[$k][0].',';
            }else{
                if($k == 'txt' || $k == 'money'){
                    $str .= $arr[$k][0].' :'.$v.';';
                }else{
                    $str .= $arr[$k][0].':'.$arr[$k][1][$v].';';
                }
            }
            
        }
        if($reset){
            $reset = '重置属性:'.rtrim($reset,',').';';
        }
        return $str.$reset;
    }

    /**
     * 获取正在出售的域名
     */
    private function getDomains($tits,$flag = false){

        $domainPro = [];
        $did = [];
        $domainChunk = array_chunk($tits, 1000);
        $field = $flag ? 'tit,userid,id' : 'tit';
        $userids = [];
        foreach($domainChunk as $v){
            $domains = $this->model->field($field)->whereIn('tit',$v)->select();
            if($domains){
                $domainPro = array_merge($domainPro,array_column($domains,'tit'));
                if($flag){
                    $userids = array_merge($userids,array_unique(array_column($domains,'userid')));
                    $did = array_merge($did,array_column($domains,'id'));
                }
            }
        }
        return [$domainPro,array_unique($userids),$did];
    }

    /**
     * 删除出售中的域名
     */
    private function delSaleDomain($did,$userid){

        $tits = [];
        $chunk = array_chunk($did,2000);
        foreach($chunk as $item){
            //获取域名及包内域名
            $tit = Db::name('domain_pro_trade')->where('userid',$userid)->where(function($query) use($item){
                $query->where(function($query) use($item){
                    $query->where('pack_id','in',$item)->where(['type' => 9]);
                })->whereOr('id','in',$item);
            })->column('tit');
            $tits = array_merge($tits,$tit);
        }
        $tits = array_unique($tits);
        $shop_type = Db::name('storeconfig')->where('userid',$userid)->value('flag');//店铺类型
        $shop_type = !$shop_type ? 0 : $shop_type;
        $redis_1 = new Redis(['select' => 1]);
        //根据域名分批,数量可能很多
        $dchukn = array_chunk($tits,1000);
        Db::startTrans();
        try{
            foreach($dchukn as $item){
                Db::name('domain_pro_trade')->whereIn('tit',$item)->delete();
                Db::name('domain_pro_n')->whereIn('tit',$item)->update(['zt' => 9]);
                Db::name('domain_follow')->whereIn('tit',$item)->update(['status' => 1]);
                Db::name('domain_order')->whereIn('tit',$item)->where('status',0)->delete();
            }
            Db::name('domain_operate_record')->insert([
                'tit' => implode(',',$tits),
                'operator_id' => $this->auth->id,
                'create_time' => time(),
                'type' => 6,
                'value' => '删除出售中的域名',
            ]);
            foreach($tits as $v){
                $redis_1->lRem('check_domain1',0,$v.'_'.$shop_type);
            }

        }catch (\Exception $e){
            Db::rollback();
            Fun::ini()->unlockKey($userid.'_domain_transaction');
            $this->error($e->getMessage());
        }

        Db::commit();
        Fun::ini()->unlockKey($userid.'_domain_transaction');
        $this->success('一口价域名删除成功','reload');
    }
}
