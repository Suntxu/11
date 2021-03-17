<?php

namespace app\admin\controller\orderfx;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 后缀设置
 *
 * @icon fa fa-user
 */
class Msglist extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::table(PREFIX.'domain_msgStu')->alias('m')->join('domain_user u','u.id=m.userid')->where($where)->count();
            $list = Db::table(PREFIX.'domain_msgStu')->alias('m')->join('domain_user u','u.id=m.userid')
                    ->field('m.read_time,m.id,m.status,u.uid')
                    ->where($where)->order($sort,$order)
                    ->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();
            foreach($list as $k=>$v){
                $list[$k]['status'] = $fun->getStatus($v['status'],['未读','已读']);
                $list[$k]['u.uid'] = $v['uid'];
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign(['status'=>$this->request->get('status'),'id'=>$this->request->get('id')]);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            // 获取父ID
            $cid =  Db::name(PREFIX.'domain_msgStu')->where('id',$ids)->value('cid');
            if(empty($cid)){
                $this->error('参数有误');
            }
            Db::table(PREFIX.'domain_msgStu')->delete($ids);
            $m = Db::name(PREFIX.'domain_msgStu')->where('cid',$cid)->count();
            // 如果消息删除的读取人数为0  就把消息删除
            if($m == 0){
                Db::table(PREFIX.'domain_msgStu')->delete($cid);
                $this->success('删除成功,请刷新父页面！');
            }
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }


}
