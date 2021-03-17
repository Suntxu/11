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
class Msg extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_msg');
    }
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model->alias('m')->field('`all`,type,id,tit,create_time')->where($where)->order($sort,$order)->limit($offset, $limit)->select();

            $ids = array_column($list,'id');
            $counts = Db::table(PREFIX.'domain_msgStu')->field('cid,count(if(status=0,1,null)) as wd,count(if(status=1,1,null)) as yd')->whereIn('cid',$ids)->group('cid')->select();
            $peoples = [];
            foreach($counts as $v){
                $peoples[$v['cid']] = $v;
            }
            // 获取消息类型列表
            $msgType = Db::name('domain_helptype')->field('id,name1')->where(['zt'=>1,'pid'=>45])->select();
            $msgTypeArr = array_column($msgType,'name1','id');

            $fun = Fun::ini();
            foreach($list as &$v){
                $v['all'] =  $fun->getStatus($v['all'],['部分用户','全部用户']);
                $v['type'] = empty($msgTypeArr[$v['type']]) ? '--' : $msgTypeArr[$v['type']];
                $v['wd'] = isset($peoples[$v['id']]) ? $peoples[$v['id']]['wd'] : 0;
                $v['yd'] = isset($peoples[$v['id']]) ? $peoples[$v['id']]['yd'] : 0;
                $v['zrs'] = $v['wd'] + $v['yd'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['tit'])){
                    $this->error('消息标题必须填写');
                }
                if(empty($params['type'])){
                    $this->error('消息类型必须选择');
                }
                $user = $this->request->post('domain');
                if($params['all'] == 0){
                    if(empty($user)){
                        $this->error('请输入用户列表');
                    }
                    $uids = Fun::ini()->moreRow($user);

                    $dom = Db::name('domain_user')->field('id,uid')->whereIn('uid',$uids)->select();
                }else{
                    $dom = Db::name('domain_user')->field('id,uid')->where('zt = 1')->select();
                }
                $params['create_time'] = time();
                $this->model->insert($params);
                $cid = $this->model ->getLastInsID();
                $ds = [];
                foreach($dom as $v){
                    $ds[]= ['cid'=>$cid,'userid'=>$v['id']];
                }
                Db::table(PREFIX.'domain_msgStu') -> insertAll($ds);
                $this -> success('添加完成');
            }else{
                $this -> error('缺少数据');
            }
        }
        //发送消息的人员列表
        $id = $this->request->get('id',0);
        $userlist = Db::name('domain_user')->whereIn('id',$id)->column('uid');
        //获取消息类型
        $msgType = Db::name('domain_helptype')->field('id,name1')->where(['zt'=>1,'pid'=>45])->select();
        $msg = array_column($msgType,'name1','id');
        $this->view->assign(['userlist'=>$userlist,'msgType'=>$msg]);
        return $this->view->fetch();
    }

    /**
     * 详情
    */
    public function show($ids='')
    {
        $id = $this->request->get('ids');
        if($id){
           $data = $this->model->find($id);
           $data['type'] = Db::name('domain_helptype')->where(['id'=>$data['type'],'zt'=>1])->value('name1');
           $this->view->assign('data',$data);
           return $this->view->fetch();
        }
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            $this->model->delete($ids);
            Db::table(PREFIX.'domain_msgStu')->where(['cid'=>$ids])->delete();
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }


}
