<?php

namespace app\admin\controller\staffuse;

use app\common\controller\Backend;
use think\Config;
use think\Session;
use think\Db;
use app\admin\common\Fun;
/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{
    /**
     * 推广系统控制台
     * 1.推广员进来，只能看到相应推广员下的一些参数
     * 2.管理员进来，可看到所有推广员参数
     * 3.推广员进来，可看到自己的推广链接
     */
    public function index()
    {
       
        $spreadurl = '';
        $uid = $this->auth->id;
       //生成推广链接 
        $admin = Session::get('admin');
        // $toptoken = md5(TOKEN_KEY.$admin['id']);
        // if(!$admin['toptoken']){
        //     model('Admin')->where(['id' => $admin['id']])->update(['toptoken' => $toptoken]);
        // }
        // $spreadurl = SPREAD_URL.'index.php?s_top='.$toptoken;

        $channel = Db::name('spread_channel')->where(['status' => 'normal'])->select();
        $m = model('category');
        foreach($channel as $k => $v){
            $c_type = $m->where("id = {$v['category_id']}")->field('name')->find();
            $channel[$k]['category_text'] = $c_type['name'];
            $channel[$k]['spreadurl'] =  SPREAD_URL;
            $channel[$k]['cid'] = $v['id'];
        }
        $where = ['topspreader'=>$uid];
        $under = Db::name('domain_user')->where($where)->count();
        $ordersum = Db::name('domain_order')->where('status=1 and sptype=1 and topid='.$uid)->field('sum(money) as n,sum(final_money) as f,sum(tmoney) as t')->find();
        //已充值的用户
        $cz_num = Db::name('domain_dingdang') ->where($where)->where('ifok=1')->count('distinct userid');
        // 获取访问量统计表 一共几个
        $total_num = $this->getRecordYearTableName('total_20');

        global $remodi_db;
        $model = Db::connect($remodi_db);

        $year = date('Y');

        $total_ipo = ' SELECT count(*) as n from '.PREFIX.'total_'.$year.' WHERE link = '.$this->auth->id.' GROUP BY DATE_FORMAT(create_time,"%Y-%m-%d"),ip';
        $total_pvo = ' SELECT count(*) as n from '.PREFIX.'total_'.$year.' WHERE link = '.$this->auth->id;
        $total_uvo = ' SELECT count(DISTINCT cookie) as n FROM '.PREFIX.'total_'.$year.' WHERE link = '.$this->auth->id;

        $res_ipo = Db::query($total_ipo);
        $res_pvo = Db::query($total_pvo);
        $res_uvo = Db::query($total_uvo);
        $nip = count(array_column($res_ipo,'n'));
        $npv = array_sum(array_column($res_pvo,'n'));
        $nuv = array_sum(array_column($res_uvo,'n'));

        $total_ip = '';
        $total_pv = '';
        $total_uv = '';
        foreach($total_num as $v){
            $total_ip .= ' SELECT count(*) as n from '.PREFIX.'total_'.$v.' WHERE link = '.$this->auth->id.' GROUP BY DATE_FORMAT(create_time,"%Y-%m-%d"),ip union all';
            $total_pv .= ' SELECT count(*) as n from '.PREFIX.'total_'.$v.' WHERE link = '.$this->auth->id.' union all';
            $total_uv .= ' SELECT count(DISTINCT cookie) as n FROM '.PREFIX.'total_'.$v.' WHERE link = '.$this->auth->id.' union all';
        }

        $res_ip = $model->query(rtrim($total_ip,'union all'));
        $res_pv = $model->query(rtrim($total_pv,'union all'));
        $res_uv = $model->query(rtrim($total_uv,'union all'));
        $this->view->assign([
            'totaluser'        => $under,
            'totalorderamount' => sprintf('%.2f',$ordersum['n']),
            'totalo' => sprintf('%.2f',$ordersum['f']),
            'uid'              => $admin['username'],
            'cz_num'           => $cz_num,
            'channel'          => $channel,
            'ip'               => (count(array_column($res_ip,'n')) + $nip),
            'pv'               => (array_sum(array_column($res_pv,'n')) + $npv),
            'uv'               => (array_sum(array_column($res_uv,'n')) + $nuv),
            'rebate'           =>sprintf('%.2f',$ordersum['t']),
        ]);
        $this->view->assign('spreadurl',$spreadurl);
        return $this->view->fetch();
    }
    public function copeurl(){
        $where = $this->request->post();
        if($where){
            $admin = Session::get('admin');
            // 查询该推广员 是否在同渠道已经有链接
            $spre = Db::name('domain_link')->field('rand')->where(['cid'=>$where['cid'],'tid'=>$admin['id'],'alink'=>$where['url']])->order('id desc')->find();
            if($spre){
                return json(['code'=>0,'uri'=>WEBURL.'tg?unqiuesd='.$spre['rand']]);
            }else{
                $params['tid'] = $admin['id'];
                $params['cid'] = $where['cid'];
                $params['alink'] = $where['url'];
                $params['rand'] = $params['tid'].Fun::ini()->rands().$params['cid'];
                Db::name('domain_link') -> insert($params);
                return json(['code'=>0,'uri'=>WEBURL.'tg?unqiuesd='.$params['rand']]);
            }
        }else{
            echo json_encode(['code'=>1]);
        }
    }

}
