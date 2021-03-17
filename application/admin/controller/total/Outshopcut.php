<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 查看外部店铺折扣
 */
class Outshopcut extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();

        global $remodi_db;

        $this->model = Db::connect($remodi_db)->name('outshop_config');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {

        if ($this->request->isPost()) {

            $domain = $this->request->param("domain");
            
            if(empty($domain)){
                $this->error('请输入要查询的域名');
            }

            $a = Fun::ini()->moreRow($domain);
            if(count($a) > 500){
                $this->error('每次最多提交500个域名');
            }
            

            $data = Db::name('domain_pro_trade_out')->whereIn('tit',$a)->field('tit,shopid')->select();

            if(empty($data)){
                $this->error('请填写合作一口价里面的域名');
            }

            $shopids = array_unique(array_column($data,'shopid'));

            $configs = $this->model->whereIn('shopid',$shopids)->column('discount','shopid');

            foreach($data as $k => &$v){
                $v['discount'] = isset($configs[$v['shopid']]) ? $configs[$v['shopid']] : 0.00;
            }


            $this->view->assign([
                'domain' => array_column($data,'tit'),
                'data' => $data,
                'unum' => count($shopids),
                'show' => 'aa',
                'total' => count($data),
            ]);
            return $this->view->fetch();
        }
        //批量修改域名列表
        $this->view->assign([
            'show' => 'aaaa',
            'total' => 0,
        ]);
        return $this->view->fetch();
    }



}

