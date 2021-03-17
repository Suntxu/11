<?php
namespace app\admin\controller\spread\elchee;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 怀米大使 域名注册记录
 *
 * @icon fa fa-user
 */
class Regdomain extends Backend
{
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
        if ($this->request->isAjax())
        {
            
            list($where, $sort, $order, $offset, $limit,$group,$year) = $this->buildparams();

            $def = '';
            if($group){
                $apiids = $this->getApis($group);
                $def = ' and api_id in ('.implode(',',$apiids).')';
            }
            $params = json_decode($this->request->param('filter'),true);

            if(isset($params['d.taskid'])){

                //提交任务时间 小于 2019-12-31 09:30:00 读取2019的表
                $taskTime = Db::table(PREFIX.'Task_record')->where('id',$params['d.taskid'])->value('createtime');

                if($taskTime < strtotime('2019-12-31 09:30:00')){
                    $year = ''; //_2019
                }else{
                    $year = '';
                }
            }
            $total = Db::name('spreader_flow')->alias('f')->join(PREFIX.'Task_record r','f.infoid=r.id and f.type = 1 and f.yjtype = 1','left')->join(PREFIX.'Task_Detail'.$year.' d','d.taskid=r.id and r.tasktype = 2')->join('domain_user u','r.userid=u.id','left')->join('domain_user u1','f.userid=u1.id')
                ->where($where)->where('d.TaskStatusCode = 2 and u1.uid is not null '.$def)
                ->count();

            $list = Db::name('spreader_flow')->alias('f')->join(PREFIX.'Task_record r','f.infoid=r.id and f.type = 1 and f.yjtype = 1','left')->join(PREFIX.'Task_Detail'.$year.' d','d.taskid=r.id and r.tasktype = 2')->join('domain_user u','r.userid=u.id','left')->join('domain_user u1','f.userid=u1.id')
                    ->field('d.money,d.tit,r.createtime,u.uid,u1.uid as uuid,d.api_id,r.a_type')
                    ->where($where)->where('d.TaskStatusCode = 2 and u1.uid is not null '.$def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
                    
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(d.money) as n FROM '.PREFIX.'spreader_flow f inner join '.PREFIX.'Task_Detail'.$year.' d on d.taskid=f.infoid AND f.type = 1 AND f.yjtype = 1 left join '.PREFIX.'domain_user u1 on f.userid=u1.id and u1.uid is not null  WHERE  d.TaskStatusCode = 2 '.$def;
            }else{
                $conm = 'SELECT sum(d.money) as n FROM '.PREFIX.'spreader_flow f inner join '.PREFIX.'Task_record r on  r.id=f.infoid AND f.type = 1 AND f.yjtype = 1  inner join '.PREFIX.'Task_Detail'.$year.' d on d.taskid=r.id and  r.tasktype = 2 left join '.PREFIX.'domain_user u on r.userid=u.id left join '.PREFIX.'domain_user u1 on f.userid=u1.id and u1.uid is not null '.$sql.' and  d.TaskStatusCode = 2 '.$def;// and c.coupon_amount != 0
            }
            $res = Db::query($conm);
            // $cates = $this->getCates();
            $apis = $this->getApis(-1);
            $fun = Fun::ini();
            //实付总金额
            foreach($list as $k => $v){
               $list[$k]['zje'] = $res[0]['n'];
               $list[$k]['d.money'] = $v['money'];
               $list[$k]['d.tit'] = $v['tit'];
               $list[$k]['d.createtime'] = date('Y-m-d H:i:s',$v['createtime']);
               $list[$k]['api_id'] = $apis[$v['api_id']]['tit'];
               $list[$k]['group'] = $apis[$v['api_id']]['regname'];
               $list[$k]['r.a_type'] = $fun->getStatus($v['a_type'],['普通','拼团','限量']);

               // 实付金额
               // $list[$k]['sfzje'] = $res[0]['s'];
               $list[$k]['u.uid'] = $v['uid'];
               $list[$k]['u1.uid'] = $v['uuid'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        //佣金流水跳过来的记录
        $get = $this->request->get();
        //佣金流水跳过来的记录
        $this->view->assign([
            'id' =>  empty($get['c_bc']) ? '' : str_replace(',','|',$get['c_bc']),
            'uid' => empty($get['uid']) ? '' : $get['uid'],
            'taskid' => empty($get['taskid']) ? '' : $get['taskid'],
        ]);
        return $this->view->fetch();
    }
}