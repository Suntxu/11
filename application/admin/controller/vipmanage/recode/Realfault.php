<?php

namespace app\admin\controller\vipmanage\recode;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use app\admin\library\Redis;
/**
 * 实名审核
 *
 * @icon fa fa-circle-o
 * @remark 主要用于管理上传到又拍云的数据或上传至本服务的上传数据
 */
class Realfault extends Backend
{

    protected $model = null;

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
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group) = $this->buildparams();
            $def = 'r.api_id = a.r_id ';
            if(!empty($group)){
                $x = mb_substr($group,0,1); //获取姓
                $m = mb_substr($group,1);
                $def .= ' and re.xing = "'.$x.'" ';
                if(!empty($m)){
                    $def .= ' and re.ming like "'.$m.'%" ';
                }
            }
            $total = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
                    ->join('apireal_error_record a','t.id=a.info_id','right')->join('user_renzheng re','t.renzheng_id=re.id')
                    ->join('user_renzhengapi r','r.info_id=t.id')->join('domain_user u','a.userid=u.id')
                    ->where($where)->where($def)
                    ->count();
            $list = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
                    ->join('apireal_error_record a','t.id=a.info_id','right')->join('user_renzheng re','t.renzheng_id=re.id')
                    ->join('user_renzhengapi r','r.info_id=t.id')->join('domain_user u','a.userid=u.id')->join('domain_api api','api.id=a.r_id')
                    ->field('t.id as tid,t.RegistrantType,a.true_name_id,a.id,a.time,a.type,a.msg,u.uid,re.xing,re.ming,r.createtime,r.auth_status,a.r_id')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $fun = Fun::ini();
            $apis = $this->getApis(-1);
            foreach($list as $k=>$v){
                $list[$k]['auth_status'] = $fun->getStatus($v['auth_status'],['未实名','实名提交失败','提交成功','认证成功','注册商实名失败',9=>'实名查询结果时模板不存在']);
                $list[$k]['r.createtime'] = $v['createtime'];
                $list[$k]['a.time'] = $v['time'];
                $list[$k]['a.r_id'] = $apis[$v['r_id']]['tit'];
                $list[$k]['t.id'] = $v['tid'];
                $list[$k]['RegistrantType'] =  $fun->getStatus($v['RegistrantType'],[1=>'个人',2=>'企业']);
                $list[$k]['a.type'] = $fun->getStatus($v['type'],['纳点']);
                $list[$k]['group'] = $v['xing'].$v['ming'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 重新实名认证
     */
    public function resetreal($ids){

        $apiInfo = Db::table(PREFIX.'domain_infoTemplate')->alias('t')
            ->join('apireal_error_record a','t.id=a.info_id','right')->join('user_renzheng re','t.renzheng_id=re.id')
            ->join('user_renzhengapi r','r.info_id=t.id and r.api_id = a.r_id')->join('domain_user u','a.userid=u.id')
            ->where(['a.id' => $ids])->whereIn('r.auth_status',[0,1])
            ->field('t.Telephone,t.Email,re.*,r.city_code,r.api_id,a.true_name_id,a.r_id')
            ->find();
        if(empty($apiInfo)){
            $this->error('api信息有误');
        }
        // 修改api认证表 实名中状态
        Db::name('user_renzhengapi')->where(['id' => $apiInfo['r_id']])->update(['auth_status' => 2]);
        // 提交任务 使用3号库
        $redis = new Redis(['select' => 3]);

        if($redis->hgetall('nadian_real_info_'.$apiInfo['true_name_id'])){
            $this->success('任务已经在队列中');
        }
        //赋值api_id
        $apiInfo['id'] = $apiInfo['api_id'];
        $redis->lpush('nadian_real_true_name_id',$apiInfo['true_name_id']);
        $redis->hMset('nadian_real_info_'.$apiInfo['true_name_id'],$apiInfo);
        $this->success('任务重新提交成功');
    }

}


