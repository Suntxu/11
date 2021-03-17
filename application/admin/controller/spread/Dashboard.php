<?php
namespace app\admin\controller\spread;
use app\common\controller\Backend;
use think\Db;
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
        $auth_id = $this->auth->getGroupIds();
        $where = 'topspreader <> 0';
        $under = Db::name('domain_user')->where($where)->count();
        $order = Db::name('domain_order')->where('status=1 and sptype=1 and topid!=0')->count();
        $ordersum = Db::name('domain_order')->where('status=1 and sptype=1 and topid!=0')->field('sum(money) as n,sum(final_money) as f,sum(tmoney) as t')->find();
        $pay = Db::name('domain_dingdang')->where($where)->where(['ifok' => 1])->sum('money1');
        $a_id = Db::name('auth_group_access')->where(['group_id'=>2])->select();
        $uid = '';
        foreach($a_id as $k => $v){
            $uid .= $v['uid'].',';
        }
        $wh = 'id in ('. rtrim($uid,',').')';
        //展示员工数量
        $user_num = Db::name('admin')->where($wh)->count();
        //已充值的用户
        $cz_num = Db::name('domain_dingdang') ->where($where)->where('ifok=1')->count('distinct userid');
         //统计表 ip pv uv  规则 24小时的IP为1个 PV 数据插入值
        // 获取访问量统计表 一共几个
        $total_num = $this->getRecordYearTableName('total_20');
        $year = date('Y');
        global $remodi_db;
        $model = Db::connect($remodi_db);

        $total_ipo = ' SELECT count(*) as n from '.PREFIX.'total_'.$year.' GROUP BY DATE_FORMAT(create_time,"%Y-%m-%d"),ip ';
        $total_pvo = ' SELECT count(*) as n from '.PREFIX.'total_'.$year;
        $total_uvo = ' SELECT count(DISTINCT cookie) as n FROM '.PREFIX.'total_'.$year;

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
            $total_ip .= ' SELECT count(*) as n from '.PREFIX.'total_'.$v.' GROUP BY DATE_FORMAT(create_time,"%Y-%m-%d"),ip union all';
            $total_pv .= ' SELECT count(*) as n from '.PREFIX.'total_'.$v.' union all';
            $total_uv .= ' SELECT count(DISTINCT cookie) as n FROM '.PREFIX.'total_'.$v.' union all';
        }

        $res_ip = $model->query(rtrim($total_ip,'union all'));
        $res_pv = $model->query(rtrim($total_pv,'union all'));
        $res_uv = $model->query(rtrim($total_uv,'union all'));
        $this->view->assign([
            'totaluser'        => $under,
            'totalviews'       => $order,
            'totalorder'       =>sprintf('%.2f',$pay),
            'totalorderamount' => sprintf('%.2f',$ordersum['n']),
            'totalo' => sprintf('%.2f',$ordersum['f']),
            'user_num'          => $user_num,
            'cz_num'            => $cz_num,
            'ip'               => (count(array_column($res_ip,'n')) + $nip),
            'pv'               => (array_sum(array_column($res_pv,'n')) + $npv),
            'uv'               => (array_sum(array_column($res_uv,'n')) + $nuv),
            'rebate'           => sprintf('%.2f',$ordersum['t']),
        ]);
        return $this->view->fetch();
    }
}
