<?php

namespace app\admin\controller\oldrecord;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名解析操作记录
 *
 * @icon fa fa-user
 */
class Parselog extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        global $remodi_db;
        $this->model = Db::connect($remodi_db);
        parent::_initialize();
    }
    /**
     * 查看
     */
    public function index()
    {
        $tables = $this->getRecordYearTableName('action_record_20');

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit,$year) = $this->buildparams();
            $year = empty($year) ? $tables[0] : $year;

            $total = $this->model->name('action_record_'.$year)
                ->alias('r')->join('domain_user u','u.id=r.userid')
                ->where($where)
                ->count();

            $list = $this->model->name('action_record_'.$year)
                ->alias('r')->join('domain_user u','u.id=r.userid')
                ->field('r.tit,r.remark,r.newstime,r.uip,u.uid')
                ->where($where)->order($sort,$order)->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        $this->view->assign('years',$tables);

        return $this->view->fetch();
    }

}
