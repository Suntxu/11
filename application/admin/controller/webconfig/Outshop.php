<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;

/**
 * 外部店铺设置
 */
class Outshop extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {   
        parent::_initialize();

        global $remodi_db;

        $this->model = Db::connect($remodi_db)->name('outshop_config');
    }
    
    /**
     * 查看
     */
    public function index()
    {   
        //设置过滤方法
        if ($this->request->isAjax())
        {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model->field('id,shopid,discount,type,create_time')->where($where)->order($sort,$order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            foreach($list as $k=>&$v){
                $v['type'] = $fun->getStatus($v['type'],['聚名']);
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
                if(empty($params['shopid'])){
                    $this->error('店铺id必须填写');
                }
                $flag = $this->model->where(['type' => $params['type'],'shopid' => $params['shopid']])->count();
                if($flag){
                    $this->error('相同的合作商类型店铺ID不能相同');
                }
                
                $params['min_price'] = empty($params['min_price']) ? 0.00 : floatval($params['min_price']);
                $params['max_price'] = empty($params['max_price']) ? 0.00 : floatval($params['max_price']);

                if($params['max_price'] && $params['min_price'] >= $params['max_price']){
                    $this->error('最大价格必须大于最小价格');
                }
                $params['create_time'] = time();
                $this->model->insert($params);
                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['shopid'])){
                    $this->error('店铺id必须填写');
                }
                $flag = $this->model->where(['type' => $params['type'],'shopid' => $params['shopid']])->where('id != '.$params['id'])->count();
                if($flag){
                    $this->error('相同的合作商类型店铺ID不能相同');
                }

                $params['min_price'] = empty($params['min_price']) ? 0.00 : floatval($params['min_price']);
                $params['max_price'] = empty($params['max_price']) ? 0.00 : floatval($params['max_price']);

                Db::startTrans();

                if($params['max_price']){
                    if($params['min_price'] >= $params['max_price']){
                        Db::rollback();
                        $this->error('最大价格必须大于最小价格');
                    }
                    Db::name('domain_pro_trade_out')->where('money',['>=',$params['min_price']],['<=', $params['max_price']])->where('shopid',$params['shopid'])->delete();
                }
                $this->model->update($params);
                
                Db::commit();

                $this->success('添加成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->field('id,shopid,type,discount,max_price,min_price')->where(['id'=>$ids])->find();
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){

            $shopids = $this->model->whereIn('id',$ids)->column('shopid');

            Db::startTrans();

                $this->model->whereIn('id',$ids)->delete($ids);

                Db::name('domain_pro_trade_out')->whereIn('shopid',$shopids)->delete();

            Db::commit();

            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
    }
}
