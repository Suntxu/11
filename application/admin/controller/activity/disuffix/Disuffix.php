<?php

namespace app\admin\controller\activity\disuffix;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 活动后缀添加
 *
 * @icon fa fa-user
 */
class Disuffix extends Backend
{
    /**
     * @var
     */
    protected $model = null;
    // protected $noNeedRight = ['getSuffixList'];
    

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_limit_houzhui');
    }
    /**
     * 查看
     */
    public function index()
    {
        
        if ($this->request->isAjax()) {   

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->alias('h')->join('domain_limit_meal m','m.id=h.lid')->where($where)->count();

            $cfield = '(SELECT sum(claim_num) FROM '.PREFIX.'domain_limit_order WHERE lid = m.id and hid = h.id ) as num,(SELECT sum(reg_num) FROM '.PREFIX.'domain_limit_order WHERE lid = m.id and hid = h.id ) as rnum'  ;

            $list = $this->model->alias('h')->join('domain_limit_meal m','m.id=h.lid')
                ->field('m.start_time,m.end_time,m.title,h.*,'.$cfield)
                ->where($where)->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            $count = $this->model->alias('h')->join('domain_limit_meal m','m.id=h.lid')->join('domain_limit_order o','m.id=o.lid and h.id=o.hid')
            ->field('sum(o.claim_num) as znum,sum(o.reg_num) as zrnum')
            ->where($where)
            ->find();
            $apis = $this->getApis();
            $fun = Fun::ini();
            $sj = time();
            foreach($list as &$v){
                $v['h.status'] = $fun->getStatus($v['status'],['停用','启用']);
                $v['znum'] = $count['znum'];
                $v['zrnum'] = $count['zrnum'];
                $v['h.lid'] = $v['lid'];
                $v['show'] = '查看';
                $v['flag'] = ($sj < $v['end_time']) ? 1 : 0;
                // $v['aid'] = empty($v['aid']) ? '--' : $apis[$v['aid']] ;
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
                if(empty($params['name']) || empty($params['lid']) || empty($params['new_colony']) || empty($params['new_num']) || empty($params['old_colony']) || empty($params['old_num']) ){ //|| empty($params['aid'])
                    $this->error('请填写必填参数');
                }
                $flag = $this->model->where(['lid' => $params['lid']])->count(); //,'aid' => $params['aid']
                if($flag){
                    $this->error('请不要选择相同的域名接口商的后缀');
                }
                $this->model->insert($params);
                $this -> success('添加成功');       
            }
        }
        $hz = Db::name('domain_houzhui')->column('name1');
        $mate = Db::name('domain_limit_meal')->field('id,title')->where('status = 1 and end_time > '.time())->select();
        $this->assign(['mate' => $mate,'hz' => $hz]);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()){
            $params = $this->request->post("row/a"); 
            
            if ($params){
                if(empty($params['new_colony']) || empty($params['new_num']) || empty($params['old_colony']) || empty($params['old_num'])){
                    $this->error('请填写必填参数');
                }
                $this->model->update($params);
                $this -> success('修改成功');       
            }
        }
        $data = $this->model->find($ids);
        $apis = $this->getApis();
        // $data['aid'] = empty($data['aid']) ? '--' : $apis[$data['aid']];

        $mate = Db::name('domain_limit_meal')->field('id,title')->where('id',$data['lid'])->value('title');


        $this->view->assign(['data' => $data,'mate' => $mate]);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       if($ids){
            //查看是否已产生订单记录
            $flag = Db::name('domain_limit_order')->where('hid',$ids)->count();
            if($flag){
                $this->error('该后缀已产生订单记录,不能进行删除操作');
            }
            Db::startTrans();
            try{
                $this->model->delete($ids);

            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
           
            Db::commit();
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }

    // /**
    //  * 获取后缀
    //  */
    // public function getSuffixList(){
    //     $lid = $this->request->post('id');
    //     $nsuffix = $this->model->where('lid',$lid)->column('name');
    //     $data = Db::name('domain_houzhui')->where('zt',1)->where('name1','not in',$nsuffix)->column('name1');
    //     return $data;
    // }

}
