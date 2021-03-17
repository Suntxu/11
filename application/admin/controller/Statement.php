<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Db;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Statement extends Backend
{
    /**
     * 查看
     */
    public function index()
    {

// 1、一口价销量、一口价总金额、完成交易店铺、完成交易用户、待付款交易
// 今天 昨天 最近7天 最近30天 不按条件
        // 一口价销量
        $SellNumSql = ""; 
        // 近七天的
        $seventtime = date('Y-m-d H:i:s',strtotime('-7 days'));
        // 近30天的
        $mothtime = date('Y-m-d H:i:s',strtotime('-30 days'));
        // 昨天
        $yesterday = date('Y-m-d H:i:s',strtotime('-1 days'));
        //今天
        $today = date('Y-m-d 00:00:00');
        $ci = date('Y-m-d H:i:s');
        // 调用存储过程
        // $result = \Think\Db::query("call total_sale('{$today}','{$yesterday}','{$seventtime}','{$mothtime}','{$ci}')");
        // 未付款 // 已付款 个数
        $sql1 = "select count(if(status=1,1,null)) as a1,count(if(status=0,1,null)) as a2 from ".PREFIX."domain_order where paytime between '{$today}' and '{$ci}' union all select count(if(status=1,1,null)) as a1,count(if(status=0,1,null)) as a2 from ".PREFIX."domain_order where paytime between '{$yesterday}' and '{$ci}' union all select count(if(status=1,1,null)) as a1,count(if(status=0,1,null)) as a2 from ".PREFIX."domain_order where paytime between '{$seventtime}' and '{$ci}' union all select count(if(status=1,1,null)) as a1,count(if(status=0,1,null)) as a2 from ".PREFIX."domain_order where paytime between '{$mothtime}' and '{$ci}' union all select count(if(status=1,1,null)) as a1,count(if(status=0,1,null)) as a2 from ".PREFIX."domain_order "; 
        $wfg = Db::query($sql1);
        // 一口价总金额
        $sql2 = "select sum(money) as c1 from ".PREFIX."domain_order where `status`=1 and paytime between '{$today}' and '{$ci}' union all select sum(money) as c2 from ".PREFIX."domain_order where `status`=1 and paytime between '{$yesterday}' and '{$ci}' union all select sum(money) as c3 from ".PREFIX."domain_order where `status`=1 and paytime between '{$seventtime}' and '{$ci}' union all select sum(money) as c4 from ".PREFIX."domain_order where `status`=1 and paytime between '{$mothtime}' and '{$ci}' union all select sum(money) as c5 from ".PREFIX."domain_order where `status`=1 ";
        $fkzje = Db::query($sql2);
        // 已成功购买得用户
        $sql3 = "select count(DISTINCT userid) as d1 from ".PREFIX."domain_order where `status`=1 and paytime between '{$today}' and '{$ci}' union all select count(DISTINCT userid) as d2 from ".PREFIX."domain_order where `status`=1 and paytime between '{$yesterday}' and '{$ci}' union all select count(DISTINCT userid) as d3 from ".PREFIX."domain_order where `status`=1 and paytime between '{$seventtime}' and '{$ci}' union all select count(DISTINCT userid) as d4 from ".PREFIX."domain_order where `status`=1 and paytime between '{$mothtime}' and '{$ci}' union all select count(DISTINCT userid) as d5 from ".PREFIX."domain_order where `status`=1 ";
        $buyuser = Db::query($sql3);
        // 已卖出得店铺
        $sql4 = " select count(DISTINCT selleruserid) as f1 from ".PREFIX."domain_order where `status`= 1 and paytime between '{$today}' and '{$ci}' union all select count(DISTINCT selleruserid) as f2 from ".PREFIX."domain_order where `status`= 1 and paytime between '{$yesterday}' and '{$ci}' union all select count(DISTINCT selleruserid) as f3 from ".PREFIX."domain_order where `status`= 1 and paytime between '{$seventtime}' and '{$ci}' union all select count(DISTINCT selleruserid) as f4 from ".PREFIX."domain_order where `status`= 1 and paytime between '{$mothtime}' and '{$ci}' union all select count(DISTINCT selleruserid) as f4 from ".PREFIX."domain_order where `status`= 1";
        $sellershop = Db::query($sql4);
        // 获取 店铺排名
        $sqlshop = 'select uid,shopname,count(*) as sale_num,sum(c.money) as sale_money from '.PREFIX.'storeconfig u,'.PREFIX.'domain_order c where u.userid=c.selleruserid and c.`status`=1 GROUP BY u.userid ORDER BY sale_num DESC LIMIT 20';
        $shoplist = Db::query($sqlshop);
        $this->view->assign([
            'shoplist'         => $shoplist,
            'yfk'              => array_column($wfg,'a1'),
            'wfk'              => array_column($wfg,'a2'),
            'sale_money'       => array_column($fkzje,'c1'),
            'buyuser'          => array_column($buyuser,'d1'),
            'sellershop'       => array_column($sellershop,'f1'),
        ]);
        return $this->view->fetch();
    }

}
