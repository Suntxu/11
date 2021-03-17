<?php

namespace app\admin\controller\domain;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
/**
 * 系统配置
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class Guishu extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    protected $model = null;
    protected $noNeedRight = ['dexport'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_pro_n');
    }
    /**
     * 
     * 查看
     */
    public function index()
    {
        $page = $this->request->get('page',0);

        if ($this->request->isPost() || $page) {
            $domain = $this->request->param("domain");
            //设置缓存
            $redis = new Redis(['select' => 7]);
            if(empty($domain)){
                $domain = $redis->get('yy_domain_guishu_'.$this->auth->id);
            }else{
                $redis->set('yy_domain_guishu_'.$this->auth->id,$domain,300);
            }
            if(empty($domain)){
                $this->error('请输入要查询的域名','/admin/domain/guishu');
            }
            $a = Fun::ini()->moreRow($domain);
            if(count($a) > 5000){
                $this->error('每次最多提交5000个域名');
            }
            
            $total = $this->model->alias('d')->join('domain_user u','d.userid=u.id')
                ->whereIn('d.tit',$a)
                ->count();
            $data = $this->model->alias('d')->join('domain_user u','d.userid=u.id')
                    ->field('d.id,d.userid,d.tit,u.uid')
                    ->whereIn('d.tit',$a)
                    ->order('d.userid asc')
                    ->paginate(1000);
            $arr = $data->items();
            $this->view->assign([
                'domain' => $domain,
                'data' => $data,
                'unum' => empty($arr) ? 0 : count(array_unique( array_column($arr,'uid') )),
                'show' => 'aa',
                'page' => empty($page) ? 1 : $page ,
                'total' => $total,
            ]);
            return $this->view->fetch();
        }
        //批量修改域名列表
        $id = $this->request->get('id',0);
        $domain = $this->model->field('tit')->where('id','in',$id)->select();
        $this->view->assign([
            'domain' => $domain,
            'show' => 'aaaa',
            'total' => 0,
        ]);
        return $this->view->fetch();
    }

    /**
     * 导出
     */
    public function dexport(){

        set_time_limit(0);
        $param = $this->request->post();
        if(empty($param['domain'])){
            $this->error('缺少重要参数');
        }

        $a = Fun::ini()->moreRow($param['domain']);
       
        if(count($a) > 5000){
            $this->error('每次最多提交5000个域名');
        }
        
        $data = $this->model->alias('d')->join('domain_user u','d.userid=u.id')
                ->field('u.uid,d.tit')
                ->whereIn('d.tit',$a)
                ->order('d.userid asc')
                ->limit(5000)
                ->select();

        if(empty($data)){
            $this->error('查询结果为空');
        }
        $filename = 'gs_'.date('YmdHis');
        if(empty($param['type'])){ //csv
            Fun::ini()->csvFile(['用户名','域名'],$data,$filename.'.csv');
        }else{ //txt
            Fun::ini()->txtFile($data,$filename.'.txt',['用户名','域名']);
        }

    }


}
