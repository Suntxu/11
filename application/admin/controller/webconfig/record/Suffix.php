<?php

namespace app\admin\controller\webconfig\record;

use app\common\controller\Backend;
use think\Db;
/**
 * 后缀设置
 *
 * @icon fa fa-user
 */
class Suffix extends Backend
{


    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_houzhui_record');
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
            $total = $this->model->alias('h')->join('domain_api a','h.aid=a.id','left')->join('admin d','h.auth_id=d.id','left')->where($where)->count();
            $list = $this->model->alias('h')->join('domain_api a','h.aid=a.id','left')->join('admin d','h.auth_id=d.id','left')
                    ->field('h.*,a.tit,d.nickname')
                    ->where($where)->order($sort,$order)->limit($offset, $limit)->select();

            // $admins = Db::name('admin')->field('id,nickname')->select();
            // $admins = array_combine(array_column($admins,'id'),array_column($admins,'nickname'));
            foreach($list as &$v){
                $v['h.create_time'] = $v['create_time'];
                $v['zt'] = $v['zt'] == 1 ? '开启' : '关闭';
                $v['discount'] = '<span style="cursor:pointer;margin-left:10px;color:grey;" id="show'.$v['id'].'" >查看</span>';

            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
