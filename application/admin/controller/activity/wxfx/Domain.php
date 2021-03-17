<?php

namespace app\admin\controller\activity\wxfx;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 系统公告
 *
 * @icon fa fa-user
 */
class Domain extends Backend
{

    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('activity_domain');
    }
    
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('t')->join('domain_pro_trade p','t.titid = p.did')
                ->where($where)
                ->count();
            $list = $this->model->alias('t')->join('domain_pro_trade p','t.titid = p.did')
                ->field('p.tit,p.money,t.titid,p.txt,t.id')
                ->where($where)->order($sort,'asc')->limit($offset, $limit)
                ->select();
            foreach($list as $k => &$v){
                $v['txt'] = Fun::ini()->returntitdian($v['txt'],80);
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
                $av=str_replace("\r","",$params['tit']);
                $a=preg_split("/\n/",$av);
                $tit = '';
                foreach($a as $k=>$v){
                    $tit .= "'{$v}',";
                }
                $tit = substr($tit,0,-1);
                $time = time();
                $domain = Db::name('domain_pro_trade') -> field('did') -> where(' tit in ('.$tit.') ') ->select();

                if($domain){
                    $sql = 'insert into '.PREFIX.'activity_domain(titid,newstime,atype) select ';
                    foreach($domain as $kk => $vv){
                        $sql .="{$vv['did']},{$time},'{$params['atype']}' from DUAL where not exists(select id from ".PREFIX."activity_domain where titid = {$vv['did']} and atype = '{$params['atype']}'),";
                    }
                    Db::execute(rtrim($sql,','));
                }
                
                $this -> success('添加成功');       
            }
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
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
      
    }


}
