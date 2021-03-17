<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\library\Redis;
/**
 * 系统配置
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class Updns extends Backend
{
    /**
     * @var \app\common\model\Config
     */
    // protected $noNeedRight = ['check'];
    

    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if(empty($params['appid'])){
                $this->error('请选对应的API');
            }
            // 域名
            $av=str_replace("\r","",$params['txt']);
            $a=preg_split("/\n/",$av);
            $a = array_filter($a);
            // 过滤数据库中的域名
            $domainList = Db::name('domain_pro_n')->whereIn('tit',$a)->column('tit');
            if($domainList){
                // 存入redis队列 7号库
                $redis = new Redis(['select' => 7,'host' => '139.199.124.220','password' => 'SrhWdK1J5t']);
                foreach ($domainList as $k => $v) {
                    $redis -> LPush('dns_api_'.$params['appid'],$v);
                }
                $this->success('任务提交成功','reload');
            }else{
                return $this->error('请输入数据库中的域名');                
            }
        }
        // 获取注册商列表  
        $list = Db::name('category')->field('id,name')->where(['type'=>'api','status'=>'normal'])->select();
        foreach($list as $k=>$v){
            $list[$k][$v['id']] =  Db::name('domain_api')->field('id,tit')->where(['status'=>'1','regid'=>$v['id']])->select();
        }
        $this->view->assign('zcs',$list);
        return $this->view->fetch();
    }
    // 加载API
    public function getApi(){
        $id = $this->request->post('id');
        if($id){
            $api = Db::name('domain_api')->field('id,tit')->where(['status'=>'1','regid'=>$id])->select();
            return json(['code'=>0,'res'=>$api]);
        }
        return json(['code'=>1,'msg'=>'加载失败']);
    }

}
