<?php

namespace app\admin\controller\spread\elchee\distr;

use app\common\controller\Backend;
use think\Db;
/**
 * 分销系统 申请中的店铺
 *
 * @icon fa fa-user
 */
class Distribuwe extends Backend
{
    protected $relationSearch = false;
    /**
     * User模型对象
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
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('distribution_log')->alias('d')->join('domain_user u','u.id=d.userid','left')->where($where)->where('d.status = 0')->count();
            $list = Db::name('distribution_log')->alias('d')->join('domain_user u','u.id=d.userid','left')
                    ->field('u.uid,d.msg,d.add_time,d.id')
                    ->where($where)->where('d.status = 0')
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 审核
     */
     public function edit($ids = ''){
        $this->request->filter(['strip_tags']);

        if($this->request->isPost()){
            $params = $this->request->post('row/a');
            $flag = Db::name('distribution_log')->where(['id'=>$params['id'],'status'=>0])->count();
            if($flag == 0){
                return $this->error('参数错误');
            }
            if($params['status'] == 2){
                $token = md5(time().$params['id'].mt_rand(1000,9000));
                // 更新token
                Db::name('storeconfig')->where(['userid'=>$params['userid']])->update(['token' => $token]);
            }
            Db::name('distribution_log')->where(['id'=>$params['id']])->update($params);
            return $this->success('操作成功');
        }   
        $data = Db::name('distribution_log')->alias('d')->join('domain_user u','u.id=d.userid','left')
                ->field('u.uid,d.userid,d.id')
                ->where(['d.id'=>intval($ids)])
                ->find();
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }
}


