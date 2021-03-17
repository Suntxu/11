<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use app\admin\library\Redis;
use app\admin\common\Fun;

/**
 * 封装导出方法
 * @internal
 */
class Export extends Backend
{

    protected $noNeedRight = ['*'];
    protected $layout = '';
    protected $model = null;

    private $def = ' 1 = 1 ';
    private $head = [];
    private $q_field = null;

    private $join = null;
    private $alias = false; 
    

    private $suffix = null;

    public function _initialize()
    {
        parent::_initialize();
        //设置过滤方法
        $this->request->filter(['strip_tags']);

    }

    /**
     * 首页
     */
        
    public function index(){

        $param = $this->request->get();
         if($this->request->isAjax() || isset($param['even']) ){ //evem 即时导出

            if(!method_exists( 'app\admin\controller\Export', $param['action'])){
                return ['code' => 1,'msg' => '类型有误!'];
            }

            if(!preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u', $param['name'])){
                return ['code' => 1,'msg' => '文件名只支持中文字母数字下划线组成'];
            }

            list($where, $sort, $order, $offset, $limit,$group,$special_condition) = $this->buildparams();
            
            call_user_func('self::'.$param['action'],[$group,$special_condition]);

            if($this->join){
                $count = $this->model->alias($this->alias)->join($this->join)->where($where)->where($this->def)->count();
            }else{
                $count = $this->model->where($where)->where($this->def)->count();
            }
            
            $limit = 10000;
            $num = ceil($count / $limit);
            $data = [];
            for($i = 0;$i < $num; $i++){
                $start = $limit * $i;

                if($this->join){
                    $sql = $this->model->alias($this->alias)->join($this->join)->where($where)->where($this->def)->field($this->q_field)->limit($start,$limit)->fetchSql(1)->select();
                }else{
                    $sql = $this->model->where($where)->where($this->def)->field($this->q_field)->limit($start,$limit)->fetchSql(1)->select();
                }
                $data[] = $sql;
            }
            
            $insert = ['userid' => - $this->auth->id,'createtime' => time(),'name' => $param['name'].$this->suffix];

            $id = Db::name('domain_export')->insertGetId($insert);

            $insert['sql'] = json_encode($data);

            $redis = new Redis(['select' => 7]);
            $redis->rpush('export_domain_operate_id',$id);
            $redis->hmset('export_domain_operate_id_'.$id,$insert);
            $redis->hmset('export_domain_head_'.$id,$this->head);
            $redis->set('export_domain_action_'.$id,$param['action']);
            exit(json_encode(['code' => 0,'msg' => '任务提交成功']));
        }
    }
    /**
     * 预定过期删除域名
     */
    private function reservedomain($swhere){
        
        global $reserve_db;

        $this->model = Db::connect($reserve_db)->name('domain_pro_reserve');

        $this->q_field = 'tit,reg_time,del_time';

        $this->head = ['域名','注册时间','删除时间'];

        $this->suffix = '_预定过期域名列表';

        if($swhere[0]){
          $this->def .= ' and hz = "'.ltrim($swhere[0],'.').'"';
        }

    }

    /**
     * 其他后缀预定域名
     */
    private function reservedomain_other($swhere){
        
        global $reserve_db;

        $this->model = Db::connect($reserve_db)->name('domain_pro_other_reserve_'.date('Ymd'));

        $this->q_field = 'tit,reg_time,del_time,icp_org,icp_name,icp_serial';

        $this->head = ['域名','注册时间','删除时间','备案性质','主办单位名称','备案号'];

        $this->suffix = '_其他后缀预定域名过期列表';
        
        if($swhere[0]){
          $this->def .= ' and hz = "'.ltrim($swhere[0],'.').'"';
        }

        if($swhere[1] == 1){
            $this->def .= ' and  icp_serial != ""';
        }elseif($where[1] == 2){
            $this->def .= ' and  icp_serial = ""';
        }

    }

    /**
     * 域名主库导出域名
     */
    private function domainpro($swhere){

        $this->model = Db::name('domain_pro_n');
        
        $this->join = [['domain_user u','p.userid=u.id','left']];
            
        $this->q_field = 'p.tit';
        
        $this->alias = 'p';

        $this->head = ['域名'];

        $this->suffix = '_全部域名列表';
        
        if($swhere[0]){

            $to = date('Y-m-d H:i');

            $date = date('Y-m-d H:i',strtotime('-25 day'));

            if($swhere[0] == 1){ // 未过期

                $this->def .= ' and p.dqsj >= "'.$to.'"';

            }elseif($swhere[0] == 2){ //过期

                $this->def .= ' and p.dqsj between "'.$date.'" and "'.$to.'"';

            }else{ //赎回
                $this->def .= ' and p.dqsj < "'.$date.'"';

            }
        }
    }
    

}
