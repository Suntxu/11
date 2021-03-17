<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 更改域名注册商
 */
class Updatezcs extends Backend
{
    protected $noNeedRight = ['getApi'];

    /**
     * @var \app\common\model\Config
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        if ($this->request->isPost()) {

            $params = $this->request->post();
            
            $zcs = empty($params['zcs']) ? 0 : intval($params['zcs']);

            if(empty($zcs)){
                $this->error('请选择注册商');
            }

            $api_id = empty($params['api_id']) ? 0 : intval($params['api_id']);

            if(empty($api_id)){
                $this->error('请选择接口商');
            }
            
            if(empty($params['ttxt'])){
                $this->error('请输入要修改的域名');
            }

            $domains = Fun::ini()->moreRow($params['ttxt']);
            
            if(count($domains) > 5000){
                $this->error('每次最多提交5000个域名');
            }

            $pros = Db::name('domain_pro_n')->whereIn('tit',$domains)->column('tit');

            if(empty($pros)){
                $this->error('域名不存在');
            }

            Db::startTrans();
            try{
                Db::name('domain_pro_n')->whereIn('tit',$pros)->update(['zcs' => $zcs,'api_id' => $api_id]);
                Db::name('domain_pro_trade')->whereIn('tit',$pros)->update(['zcs' => $zcs,'api_id' => $api_id]);

            }catch(Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            
            Db::commit();

            $this->success('修改成功','reload');

        }
        $this->view->assign('zcs',$this->getCates('api',false));
        return $this->view->fetch();
    }

    // 加载API
    public function getApi(){
        $id = $this->request->post('regid',0);
        if($id){
            $apis = $this->getApis(-1);
            $arr = [];
            foreach($apis as $k => $v){
                if($v['regid'] == $id){
                    $arr[$k]['id'] = $v['id'];
                    $arr[$k]['tit'] = $v['tit'];
                }
            }
            return json(['code'=>0,'res' => $arr]);
        }
        return json(['code'=>1,'msg'=>'加载失败']);
    }

}
