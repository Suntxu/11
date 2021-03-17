<?php

namespace app\admin\controller\total;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名过期删除记录
 *
 * @icon fa fa-user
 */
class Expdomain extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('expdomain_delrecord');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();

            $def = '';
            if($group){
              if(strrpos($group,'.')){
                $dit = -2;
              }else{
                $dit = -1;
              }
              $def .= ' substring_index(tit,".",'.$dit.') = "'.ltrim($group,'.').'"';
            }
            
            $total = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                          ->where($where)->where($def)
                          ->count();
            $list = $this->model->alias('r')->join('domain_user u','r.userid=u.id')
                    ->field('u.uid,r.tit,r.dqsj,r.zcsj,r.del_time')
                    ->where($where)->where($def)->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach($list as $k => $v){
              $list[$k]['r.del_time'] =  $v['del_time'];
              $list[$k]['r.tit'] = $v['tit'];
              $list[$k]['group'] = strstr($v['tit'],'.',false);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

}
