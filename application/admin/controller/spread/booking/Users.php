<?php
namespace app\admin\controller\spread\booking;
use app\common\controller\Backend;
use think\Db;
//参团展示控制器
class Users extends Backend
{
    
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('assemble_order');
    }
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $what = $this->model->alias('a')->join('domain_user b','a.uid=b.id','left')
                ->field('a.*,b.uid as name')
                ->where($where)->order($sort,$order)->limit($offset, $limit)
                ->select();
            $total  =   count($what);
            $result = array("total" => $total,"rows" => $what);
            return  json($result);
        }
        $data   =   $this->request->param();
        $id     =   $data['mid'];
        $this->assign('id',$id);
        return $this->view->fetch();
    }
    public function del($ids    =   '')
    {
        $id['id']   =   $ids;
        $list   =   Db::name('assemble_order')->delete($id);
        if (isset($list))
        {
            return  $this->success('删除成功');
        }
        else{
            return  $this->error("删除失败");
        }
    }
}
