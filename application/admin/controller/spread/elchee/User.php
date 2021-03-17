<?php

namespace app\admin\controller\spread\elchee;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 推广用户
 *
 * @icon fa fa-user
 */
class User extends Backend
{
    protected $noNeedRight = ['updateRebate'];

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

            $fieldSql = '(select sum(d.money1) from  '.PREFIX.'domain_dingdang d left join '.PREFIX.'domain_promotion_relation_log l on d.userid=l.userid where d.ifok = 1 and l.relation_id = p.userid and d.sj between FROM_UNIXTIME(l.stime,"%Y-%m-%d %H:%i:%S") and FROM_UNIXTIME(l.etime,"%Y-%m-%d %H:%i:%S") ) as chzj,';

            $year = date('Y');
            //统计今年表
            // 访问量
            $fieldSql .= '(select count(*) from '.PREFIX.'domain_promotion_material_log_'.$year.' where type = 1 and puserid = p.userid ) as visitorCount,';
            // 注册量
            $fieldSql .= '(select count(*) from '.PREFIX.'domain_promotion_material_log_'.$year.' where type = 2 and puserid = p.userid ) as regCount';

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('domain_promotion')->alias('p')->join('domain_user u','p.userid=u.id','left')
                    ->where($where)
                    ->count();
            
            $list = Db::name('domain_promotion')->alias('p')->join('domain_user u','p.userid=u.id','left')
                    ->field('p.id,p.status,u.uid,p.ctime,p.extracted_reward,p.wait_reward,p.rebate,'.$fieldSql)
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            // 单独统计
            $totNum = Db::name('domain_promotion')->alias('p')->join('domain_user u','p.userid=u.id','left')
                    ->field($fieldSql)
                    ->where($where)
                    ->select();
            $chzj = 0;
            $visitorCount = 0;
            $regCount = 0;
            $fun = Fun::ini();
            foreach($totNum as $k => $v){
                $chzj += sprintf('%.2f',$v['chzj']);
                $visitorCount += $v['visitorCount'];
                $regCount += $v['regCount'];
            }
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(wait_reward) as n,sum(extracted_reward) as s from '.PREFIX.'domain_promotion';
            }else{
                $conm = 'SELECT sum(wait_reward) as n,sum(extracted_reward) as s FROM '.PREFIX.'domain_promotion p left join  '.PREFIX.'domain_user u on p.userid=u.id '.$sql;
            }
            $res = Db::query($conm);
            $yy = $res[0]['s']/100;
            $wd = $res[0]['n']/100;
            $zje = $yy+$wd;
            $marketings = \think\Config::get('self_marketing');
            foreach($list as $k => $v){
                $list[$k]['p.status'] = $fun->getStatus($v['status'],['<span style="color:gray">未审核</span>','<span style="color:green">审核通过</span>','<span style="color:red">审核被拒绝</span>']);
                $list[$k]['yy'] = $yy;
                $list[$k]['u.uid'] = $v['uid'];
                if(isset($marketings[$v['uid']])){
                    $list[$k]['u.uid'].= ' -- '.$marketings[$v['uid']];
                }
                $list[$k]['wd'] = $wd;
                $list[$k]['zje'] = $zje;
                $list[$k]['extracted_reward'] = $v['extracted_reward']/100;
                $list[$k]['wait_reward'] = $v['wait_reward']/100;
                // 每行总金额
                $list[$k]['zz'] = $list[$k]['wait_reward']+$list[$k]['extracted_reward'];
                $list[$k]['chzj'] = sprintf('%.2f',$v['chzj']);
                // 充值量
                $list[$k]['zczl'] = $chzj;
                // 访问量
                $list[$k]['zfwl'] = $visitorCount;
                // 注册量
                $list[$k]['zzcl'] = $regCount;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this -> view -> assign('id',$this->request->get('nickname'));
        return $this->view->fetch();
    }



    public function edit($ids = NULL)
    {
        if($this->request->isAjax()){
            $param = $this->request->post('row/a');
            $list = Db::name('domain_promotion')->field('id')->where(['id' => $param['id'],'status' => 0])->find();
            if(empty($list)){
                $this->error('该记录不存在或已经被审核！');
            }
            if(empty($param['remark'])){
                $param['remark'] = $param['status'] == 1 ? '审核成功' : '审核失败';
            }
            Db::name('domain_promotion')->where('id',$param['id'])->update($param);
            $this->success('操作成功！');
        }
        $data = Db::name('domain_promotion')->alias('p')->join('domain_user u','p.userid=u.id','left')
            ->field('p.id,p.status,u.uid,p.ctime,p.remark')
            ->where(['p.id' => $ids])
            ->find();
        $this->view->assign('data',$data);
        return $this->view->fetch();
    }

    /**
     * 点击后修改返点比例
     */
    public function updateRebate()
    {
        $data = $this->request->post();
        $field = isset($data['field']) ? trim($data['field']) : '';
        if($field){
            $oldval = Db::name('domain_promotion')->where(['id'=>intval($data['id'])])->value($field);
            if($data['val'] > 100){
                return ['code' => 1,'msg' => '分销返佣比例不得超过100%','val' => $oldval];
            }
            Db::name('domain_promotion')->where(['id'=>intval($data['id'])])->update([$field=>$data['val']]);
            return ['code' => 0,'msg' => '更新成功','val' => $data['val']];
        }else{
            return ['code' => 1,'msg' => '缺少字段值','val' => 0];
        }
    }

}


