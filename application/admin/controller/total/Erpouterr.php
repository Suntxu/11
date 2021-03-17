<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
/**
 * erp一口价
 *
 * @icon fa fa-user
 */
class Erpouterr extends Backend
{
    
    protected $model = null;

    public function _initialize()
    {
        global $remodi_db;
        parent::_initialize();
        $this->model = Db::connect($remodi_db)->name('erp_outerror');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();

            $list = $this->model->field('id,tit,time,msg,money')
                ->where($where)->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids=null){

      if($this->request->isAjax()){
        $this->model->whereIn('id',$ids)->delete();
        $this->success('请求成功');
      }

    }

}
