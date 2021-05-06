<?php

namespace app\admin\controller\domain\into;

use app\common\controller\Backend;
use think\Db;
use app\admin\common\sendMail;
use app\admin\common\Fun;
use think\Config;

/**
 * 域名转回记录
 *
 * @icon fa fa-user
 */
class Intolist extends Backend
{

    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('batch_into');
    }
    /**
     * 查看
     */
    public function index($ids = '')
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //带别名 为了防止从资金明细跳过来报错
            $total = $this->model->alias('b')->where($where)->count();
            $list = $this->model->alias('b')->field('remark,bath,audit,targetuser,reg_id,email,subdate,finishdate,id,dcount,special,(select moneynum from '.PREFIX.'domain_baomoneyrecord where type=1 and status = 1 and infoid=b.id) as moneynum')
                    ->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT count(if(b.audit=2,1,null)) as etotal,count(if(b.audit=1,1,null)) as stotal,count(if(b.audit=3,1,null)) as ctotal FROM '.PREFIX.'domain_into i left join '.PREFIX.'batch_into b on i.bid=b.id ';
                $conm1 = 'SELECT sum(moneynum) as n FROM '.PREFIX.'domain_baomoneyrecord WHERE type=1 and status=1';
            }else{
                $conm = 'SELECT count(if(b.audit=2,1,null)) as etotal,count(if(b.audit=1,1,null)) as stotal,count(if(b.audit=3,1,null)) as ctotal FROM '.PREFIX.'domain_into i left join '.PREFIX.'batch_into b on i.bid=b.id '.$sql;
                $conm1 = 'SELECT sum(n.moneynum) as n FROM '.PREFIX.'batch_into b right join '.PREFIX.'domain_baomoneyrecord n on n.infoid=b.id '.$sql.' and n.type=1 and n.status=1';
            }
            $res = Db::query($conm);
            $res1 = Db::query($conm1);
            $fun = Fun::ini();
            $cates = $this->getCates();
            foreach($list as $k => $v){
                $list[$k]['remark'] = '<span style="cursor:pointer;" title="'.$v['remark'].'">'.$fun->returntitdian($v['remark'],8).'</span>';
                $list[$k]['special'] = $fun->getStatus($v['special'],['普通','<sapn style="color:orange;">预释放</span>','<sapn style="color:red;">0元转回</span>']);
                $list[$k]['manmage'] = "<a  href='/admin/domain/into/shows?id={$v['id']}' class='btn-dialog' >查看</a>";
                if($v['audit']==1){
                    $list[$k]['audit'] = '<a style="color:green">审核成功(<span title="共计'.$v['dcount'].'条" style="color:green;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==0){
                    $list[$k]['manmage'] .= "&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(4,{$v['id']})'>处理</a>&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(2,{$v['id']})'>失败</a>";
                    $list[$k]['audit'] = '<a style="color:orange">等待处理(<span title="共计'.$v['dcount'].'条" style="color:gray;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==3){
                    $list[$k]['audit'] = '<a style="color:gray">已撤销(<span title="共计'.$v['dcount'].'条" style="color:gray;">'.$v['dcount'].'</span>)';
                }elseif($v['audit']==4){
                    $list[$k]['audit'] = '<a style="color:red">审核中(<span title="共计'.$v['dcount'].'条" style="color:green;">'.$v['dcount'].'</span>)';
                    $list[$k]['manmage'] .= "&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(1,{$v['id']})'>成功</a>&nbsp;&nbsp;<a href='javascript:;' onclick='setStat(2,{$v['id']})'>失败</a>";
                }else{
                    $list[$k]['audit'] = '<a style="color:red">处理失败(<span title="共计'.$v['dcount'].'条" style="color:red;">'.$v['dcount'].'</span>)';
                }
                $list[$k]['xtotal'] = empty($res1[0]['n']) ? 0 : $res1[0]['n'];
                $list[$k]['szmsg'] = '审核成功:<span style="color:green">'.$res[0]['stotal'].'个&nbsp;&nbsp;</span>审核失败:<span style="color:red">'.$res[0]['etotal'].'个&nbsp;&nbsp;</span>已撤销:<span style="color:gray">'.$res[0]['ctotal'].'</span>';
                $list[$k]['reg_id'] = empty($cates[$v['reg_id']]) ? '--' : $cates[$v['reg_id']];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$ids);
        $this->assignconfig('fail_select',Config::get('domain_into_fail_select'));
        return $this->view->fetch();
    }
    /*
    列表页转回域名 修改状态
     */
    public function  UpdateS(){

        $params = $this->request->post();
        $id = intval($params['id']);
        $status = intval($params['status']);
        if($id){
			$sj = time();
			$info = $this->model->where('id = '.$id.' and audit in(0,4)')->field('userid,sxf')->find();
			if(!$info)
				exit('转回批次不存在或已审核');
            $arr = Db::name('domain_into')->field('domain_id,domian')->where(['bid'=>$id])->select();
			if(!$arr)
				exit('操作成功');
			Db::startTrans();
            if($status == 1){
                $tit = '';
                foreach($arr as $k => $v){
                    $tit.="'{$v['domian']}',"   ;
                }
                $tit = rtrim($tit,',');
                //域名表
                Db::name('domain_pro_n')->where('zt = 5 and tit in ('.$tit.')')->delete();
                Db::name('domain_follow')->where('tit in ('.$tit.')')->delete();
                //解析记录
                Db::name('action_record')->where('tit in ('.$tit.')')->delete();
                Db::name('domain_record')->where('tit in ('.$tit.')')->delete();
            }elseif($status == 2){
                $domain_id = array_column($arr,'domain_id');
                Db::name('domain_pro_n')->whereIn('id',$domain_id)->update(['zt'=>9]);
            }elseif($status == 4){
                $this->model->where('id',$id)->update(['audit' => 4,'admin_id' => $this->auth->id]);
                Db::commit();
                echo '操作成功';die;
            }
			if($info['sxf'] > 0){
				if($status == 1){
                        Db::name('domain_baomoneyrecord')->where(['infoid' => $id,'type' => 1])->update(['otime' => $sj,'status' => 1,'sremark' => '转回成功，已扣除']);
                        Fun::ini()->lockFreezing($info['userid']) || $this->error('系统繁忙,请稍后操作!');
                        // Db::name('domain_user')->where(['id' => $info['userid']])->setDec('baomoney1',$info['sxf']);
                        Db::execute('update '.PREFIX.'domain_user set baomoney1 = baomoney1 -'.$info['sxf'].',money1 = money1 -'.$info['sxf'].' where id = '.$info['userid']);
                        Fun::ini()->unlockFreezing($info['userid']);
                        // 获取用户余额和ip
                        $userInfo = Db::name('domain_baomoneyrecord')->field('uip,sj')->where(['infoid'=>$id,'type'=>1])->find();
                        $umoney = Db::name('domain_user')->where('id',$info['userid'])->value('money1');
                        // 关联存的是 id  batch_into
                        Db::name('flow_record')->insert([
                            'sj'    => date('Y-m-d H:i:s'),
                            'infoid'=> $id,
                            'product'=> 1,
                            'subtype'=> 3,
                            'uip'   => empty($userInfo['uip']) ? '127.0.0.1' : $userInfo['uip'],
                            'balance' => $umoney,
                            'money' => -$info['sxf'],
                            'userid'=> $info['userid'],
                        ]);
                }else{
					Db::name('domain_baomoneyrecord')->where(['infoid' => $id,'type' => 1])->update(['otime' => $sj,'status' => 2,'sremark' => '转回失败，已还原']);

                    Fun::ini()->lockBaoMoney($info['userid']) || $this->error('系统繁忙,请稍后操作!');

                    Db::name('domain_user')->where('id',$info['userid'])->setDec('baomoney1',$info['sxf']);

                    Fun::ini()->unlockBaoMoney($info['userid']);
					// Db::execute("update ".PREFIX."domain_user set baomoney1 = baomoney1 - {$info['sxf']},money1 = money1 + {$info['sxf']} where id = {$info['userid']}");
				}
			}
            
            //修改备注
            $this->model->where(['id'=>$id])->update(['remark'=>$params['remark'],'finishdate'=>$sj,'audit'=>$status,'admin_id' => $this->auth->id]);
            
            Db::commit();
            //发送邮件
            $mail = new sendMail();
            $mail -> domain_into_send($arr,$id,$status);
            echo '操作成功';
            die;
        }else{
            echo '请先选择要审核的域名！';
            die;
        }


    }



}
