<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
/**
 * 域名转入记录
 *
 * @icon fa fa-user
 */
class Shift extends Backend
{
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_access');
    }
    /**
     * 查看
     */
    public function index($ids = '')
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('b')->join('domain_user u','u.id=b.userid')->join('admin ad','ad.id=b.admin_id')->where($where)->count();
            $list = $this->model->alias('b')->join('domain_user u','u.id=b.userid')->join('admin ad','ad.id=b.admin_id')
                    ->field('b.bath,b.audit,b.email,b.subdate,b.finishdate,b.id,b.dcount,u.uid,ad.nickname,b.reg_id,b.api_id')
                    ->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $apis = $this->getApis(-1);
            foreach($list as $k => $v){
                $list[$k]['manmage'] = "<a  href='/admin/domain/access/shows?id={$v['id']}' class='btn-dialog' >查看</a>";
                if($v['audit']==1){
                    $list[$k]['audit'] = '<a style="color:green">执行成功(<span title="共计'.$v['dcount'].'条" style="color:green;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==0){
                    $list[$k]['audit'] = '<a style="color:red">待审核(<span title="共计'.$v['dcount'].'条" style="color:green;">'.$v['dcount'].'</span>)';
                }elseif($v['audit'] == 3){
                    $list[$k]['audit'] = '<a style="color:gray">任务执行中(<span title="共计'.$v['dcount'].'条" style="color:gray;">'.$v['dcount'].'</span>)';
                }else{
                    $list[$k]['audit'] = '<a style="color:red">审核失败(<span title="共计'.$v['dcount'].'条" style="color:red;">'.$v['dcount'].'</span>)';
                }
                $list[$k]['u.uid'] = $v['uid'];
                $list[$k]['ad.nickname'] = $v['nickname'];
                $list[$k]['b.email'] = $v['email'];
                if(isset($apis[$v['api_id']])){
                    $list[$k]['b.reg_id'] = $apis[$v['api_id']]['regname'];
                    $list[$k]['b.api_id'] = $apis[$v['api_id']]['tit'];
                }else{
                    $list[$k]['b.reg_id'] = '--';
                    $list[$k]['a.api_id'] = '--';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }




}