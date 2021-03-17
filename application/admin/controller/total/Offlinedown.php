<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
/**
 * 离线下载列表
 *
 * @icon fa fa-user
 */
class Offlinedown extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_export');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

         
            $total = $this->model->alias('e')->join('admin a','a.id = substring_index(e.userid,"-",-1)','left')->where($where)->where('e.userid < 0')->count();
            
            $list = $this->model->alias('e')->join('admin a','a.id = substring_index(e.userid,"-",-1)','left')
                    ->field('a.nickname,e.createtime,e.status,e.num,e.name,e.endtime,e.path')
                    ->where($where)->where('e.userid < 0')->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach($list as &$v){
              if($v['status'] == 0){
                 $v['down'] = '--';
              }else{
                 $v['down'] = '<a href="/uploads/offline/'.$v['path'].'" target="_blank">下载</a>';
              }
              $v['e.status'] = empty($v['status']) ? '<span color:red>生成中</span>' : '<span color:blue>已生成</span>' ;
              $v['e.name'] = $v['name'];
              $v['e.createtime'] = $v['createtime'];
              $v['e.endtime'] = $v['endtime'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

}
