<?php

namespace app\admin\controller\webconfig\record;

use app\common\controller\Backend;
use think\Db;
/**
 * 回收设置记录
 *
 * @icon fa fa-user
 */
class Recycle extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('recycle_config_record');
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
            $total = $this->model->alias('r')->join('admin d','r.auth_id=d.id')->where($where)->count();
            $list = $this->model->alias('r')->join('admin d','r.auth_id=d.id')
                    ->field('r.*,d.nickname')
                    ->where($where)->order($sort,$order)->limit($offset, $limit)->select();

            foreach($list as &$v){
                $v['r.create_time'] = $v['create_time'];
                $v['r.status'] = $v['status'] == 1 ? '关闭' : '开启';

            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
