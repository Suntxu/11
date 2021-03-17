<?php

namespace app\admin\controller\oprecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;

/**
 * 操作员记录--域名转回
 *
 * @icon fa fa-user
 */
class Intolist extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('batch_into');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('b')->join('admin a','a.id=b.admin_id')->where('b.audit != 0')->where($where)->count();
            $list = $this->model->alias('b')->join('admin a','a.id=b.admin_id')->where('b.audit != 0')
                    ->field('b.bath,b.audit,b.targetuser,b.email,b.subdate,b.finishdate,b.id,b.dcount,b.special,b.reg_id,(select moneynum from '.PREFIX.'domain_baomoneyrecord where type=1 and status = 1 and infoid=b.id) as moneynum,a.nickname')
                    ->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $cates = $this->getCates();
            $fun = Fun::ini();
            foreach($list as $k => $v){
                $list[$k]['b.special'] = $fun->getStatus($v['special'],['普通','<sapn style="color:orange;">预释放</span>','<sapn style="color:red;">0元转回</span>']);
               if($v['audit']==1){
                    $list[$k]['b.audit'] = '<a style="color:green">审核成功(<span title="共计'.$v['dcount'].'条" style="color:green;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==0){
                    $list[$k]['b.audit'] = '<a style="color:orange">等待处理(<span title="共计'.$v['dcount'].'条" style="color:gray;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==3){
                    $list[$k]['b.audit'] = '<a style="color:gray">已撤销(<span title="共计'.$v['dcount'].'条" style="color:gray;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==4){
                    $list[$k]['b.audit'] = '<a style="color:red">审核中(<span title="共计'.$v['dcount'].'条" style="color:red;">'.$v['dcount'].'</span>)';
                    
                }else{
                    $list[$k]['b.audit'] = '<a style="color:red">处理失败(<span title="共计'.$v['dcount'].'条" style="color:red;">'.$v['dcount'].'</span>)';
                }
                $list[$k]['name'] = empty($cates[$v['reg_id']]) ? '--' : $cates[$v['reg_id']];
                $list[$k]['a.nickname'] = $v['nickname'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


}
