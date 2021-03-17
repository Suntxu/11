<?php
namespace app\admin\controller\spread\elchee;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 红包管理
 *
 * @icon fa fa-user
 */
class Redpacket extends Backend
{
    protected $model = null;
    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model =Db::name('domain_coupon');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)->order($sort, $order)->limit($offset, $limit)->select();
            $fun = Fun::ini();
            $admins = array_flip($this->getAdminNickname());
            foreach($list as $k => $v){
                $list[$k]['admin_id'] = $admins[$v['admin_id']];
                $list[$k]['type'] = $fun->getStatus($v['type'],['所有用户']);
                $list[$k]['status'] = $fun->getStatus($v['status'],['已下架','已启用']);
                $list[$k]['use_goods'] = $fun->getStatus($v['use_goods'],['不限','满减']);
                $list[$k]['use_type'] = $fun->getStatus($v['use_type'],['--','一口价']);
                $list[$k]['rebate_amount'] = sprintf('%.2f',($v['rebate_amount']/100));
                $list[$k]['satisfy_amount'] = sprintf('%.2f',($v['satisfy_amount']/100));
                if($v['use_shop'] == '0'){
                    $list[$k]['use_shop'] = '不限';
                }else{ 
                    //暂为用户名
                    $list[$k]['use_shop'] = Db::name('domain_user')->where(['id'=>$v['use_shop']])->value('uid');
                }
                if($v['number'] == '0'){
                    $list[$k]['number'] = '不限'; 
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add($flag='')
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if(empty($params['use_shop']) || $params['use_shop'] == '不限' ){
                    $params['use_shop'] = 0;
                }else{
                    $params['use_shop'] = Db::name('storeconfig')->alias('s')->join('domain_user u','u.id=s.userid','left')->where('u.uid',$params['use_shop'])->value('u.id');
                    if(empty($params['use_shop'])){
                        $this->error('填写的用户名不存在或店铺尚未开通');
                    }
                }
                $params['satisfy_amount'] = $params['satisfy_amount']*100;
                $params['rebate_amount'] = $params['rebate_amount']*100;
                $params['admin_id'] = $this->auth->id;
                $params['ctime'] = time();
                $params['etime'] = strtotime($params['etime']);
                $params['stime'] = strtotime($params['stime']);
                $this->model->insert($params);
                $this->success('添加成功');
            }
            $this->error();
        }
        return $this->view->fetch();
    }
     /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if(empty($params['use_shop'])  || $params['use_shop'] == '不限'){
                    $params['use_shop'] = 0;
                }else{
                    $params['use_shop'] = Db::name('storeconfig')->alias('s')->join('domain_user u','u.id=s.userid','left')->where('u.uid',$params['use_shop'])->value('u.id');
                    if(empty($params['use_shop'])){
                        $this->error('填写的用户名不存在或店铺尚未开通');
                    }
                }
                $params['satisfy_amount'] = $params['satisfy_amount']*100;
                $params['rebate_amount'] = $params['rebate_amount']*100;
                $params['etime'] = strtotime($params['etime']);
                $params['stime'] = strtotime($params['stime']);
                $this->model->update($params);
                $this->success('修改成功');
            }
            $this->error();
        }
        $data = $this->model->find($ids);
        if(empty($data['use_shop'])){
            $data['use_shop'] = '不限';
        }else{
            $data['use_shop'] = Db::name('domain_user')->where(['id'=>$data['use_shop']])->value('uid');
        }
        $data['satisfy_amount'] = $data['satisfy_amount']/100;
        $data['rebate_amount'] = $data['rebate_amount']/100;
        $this->view->assign("data", $data);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $this->model->delete($ids);
            $this->success('操作成功');
        }
        $this->error();
    }
}
