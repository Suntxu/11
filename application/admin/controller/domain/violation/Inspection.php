<?php

namespace app\admin\controller\domain\violation;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
use fast\Http;
/**
 * 自查违规域名上报管理列表
 */
class Inspection extends Backend
{
    private $connect = null;

    public function _initialize()
    {
        global $violation_db;
        $this->connect = Db::connect($violation_db);
        parent::_initialize();
    }
    /**
     * 查看 自查列表
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total =  $this->connect->name('domain_violation_oneself')->where($where)->count();

            $list = $this->connect->name('domain_violation_oneself')
                    ->field('id,tit,uid,type,cause,create_time,is_redirect,registrar,img_path,is_img')
                    ->where($where)
                    ->order($sort, $order)->limit($offset, $limit)
                    ->select();

            $fun = Fun::ini();
            $cats = $this->getCates();
            foreach($list as &$v){
                $v['registrar'] = isset($cats[$v['registrar']]) ? $cats[$v['registrar']] : '';
                $types = explode(',',$v['type']);
                $type = '';
                foreach($types as $vv){
                    $type .= ' '.$fun->getStatus($vv,['','百度敏感词','综合敏感词','暴恐','反动','民生','色情','贪腐','其他','百度过滤词']);
                }
                if(mb_strlen($type) > 7){
                    $v['type'] = $fun->returntitdian($type,7).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$type.'\')" >查看更多</span>';
                }else{
                    $v['type'] = $type;
                }
                $v['is_redirect'] = $fun->getStatus($v['is_redirect'],['否','是']);
                if(mb_strlen($v['cause']) > 7){
                    $v['cause'] = $fun->returntitdian($v['cause'],7).'<span style="cursor:pointer;margin-left:10px;color:grey;"  onclick="showRemark(\''.$v['cause'].'\')" >查看更多</span>';
                }
                $v['is_img'] = $fun->getStatus($v['is_img'],['<span style="color: red;">未截图</span>','<span style="color: gray;">未上传</span>','<span style="color: green;">已上传</span>']);
                $v['c_tit'] = $v['tit']; //批量拷贝使用
                $v['tit'] = '<span style="cursor:pointer;color:#66B3FF;" onclick="copyData(\''.$v['tit'].'\')">'.$v['tit'].'</span>';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 手动添加
     */
    public function add(){

        if($this->request->isAjax()){
            $param = $this->request->post();
            $uid = empty($param['uid']) ? '' : trim($param['uid']);

            $tits = empty($param['domain']) ? '' : Fun::ini()->moreRow($param['domain']);

            if(!$uid){
                $this->error('请填写用户名');
            }

            $userid = Db::name('domain_user')->where('uid',$uid)->value('id');
            if(empty($userid)){
                $this->error('用户不存在,请确认！');
            }

            $reqParam['uid'] = $uid;
            $reqParam['analysis'] = (isset($param['punish_type']) && $param['punish_type'] == 1) ? 2 : 1;

            if($tits){
                if(count($tits) > 1000){
                    $this->error('最多可输入1000个域名');
                }

                $udomains = Db::name('domain_pro_n')->where('userid',$userid)->whereIn('tit',$tits)->column('tit');
                if(!$udomains){
                    $this->error('该批域名不存在'.$uid.'账户下面');
                }
                if($diffDomains = array_diff($tits,$udomains)){
                    $this->error('域名:'.implode(',',$diffDomains).' 不存在'.$uid.'账户中');
                }

                $tis = $this->connect->name('domain_violation_oneself')->where(['userid' => $userid,'is_img' => 2])->whereIn('tit',$tits)->column('tit');
                if($tis){
                    $this->error('域名：'.implode(',',$tis).'已有违规截图！');
                }

                $reqParam['domains'] = implode(',',$tits).',';
            }else{
                $ucount = Db::name('domain_pro_n')->where('userid',$userid)->count();
                if($ucount == 0){
                    $this->error('该账户下面没有域名,请确认！');
                }
            }

            //调用接口
            $result = json_decode(Http::post(PYTHON_API_URL.'/batch/api/scan',$reqParam),true);
            if(isset($result['code']) && $result['code'] == 1){
                $this->success('提交成功,正在扫描中！');
            }
            $this->error('接口返回失败');

        }
        return $this->view->fetch();
    }
    /**
     * 取消 自查列表
     */
    public function del($ids=null){

        if($this->request->isAjax()){

            $this->connect->name('domain_violation_oneself')->whereIn('id',$ids)->delete();

            $this->success('删除成功');

        }

    }


}
