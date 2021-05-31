<?php

namespace app\admin\common;

use think\Db;
use mailphp\SendM;
use app\admin\common\Fun;
use app\admin\common\SemdMot;
use app\admin\library\Redis;
use think\migration\command\migrate\Status;

header("Content-Type:text/html;charset=utf-8");
/**
 * 邮件发送
 */
class sendMail
{
    //域名转回审核列表发送邮件
    public function domain_into_send($domain,$id,$status)
    {   
        $userinfo = Db::name('batch_into')->field('userid,email')->where('id',$id)->find();
        $mmu = count($domain);
        //发送提示邮件
        $txt="尊敬的怀米网用户，您好！<br>您以下<span style='color:red;'>".$mmu."</span>个域名转回原注册商通知，您可以登录怀米网--用户中心--转回原注册商中<a href='".WEBURL."user/domain_into/originalRegistrar.php'>查看详情</a>。<br>如果您对本次通知内容、操作过程有任何疑问，请<a href='".WEBURL."user/#/user/help/workorder'>提交工单</a>进行反馈。<br><span style='color:red;'>*阿里云域名接收流程：控制台 > 域名 > 我是买家 > 收到的push</span><br>";
       
        $d1 = date('Y-m-d H:i:s');
        if($status == 1){
            $au = "<span style='color:green;'>转回原注册商成功</span>";
        }else{
            $au = "<span style='color:red;'>转回原注册商失败</span>";
            $txt .= '附:阿里云账号未绑定支付宝不能接收带价push解决方法请<a href="http://www.huaimi.com/help/index/details?type=&hid=163" target="_blank">点击查看</a>！<br>';
        }
        $txt .= "<table style='width:800px;border: 1px dashed gray;'><tr><th width='120px'>编号</th><th align='left'>域名</th><th>时间</th><th>转回原注册商结果</th></tr>";
        foreach($domain as $k=>$v){
            if($k >= 20){
                break;
            }
            $txt .="<tr><td align='center'>{$id}</td><td align='left'>{$v['domian']}</td><td align='center'>{$d1}</td><td align='center'>{$au}</td></tr>";
        }
        if($mmu >= 20){
            $txt .= "<tr><td colspan='4' align='left'><span style='font-size:23px;margin-left:88%;'><a href='".WEBURL."user/domain_into/originalRegistrarShow.php?id=".$id."'>查看更多</a></span></td></tr>";
        }
        $txt .= "</table>";
        $txt .= "<br>此为系统邮件，请勿回复。<br>";
        $txt .= "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '域名转回原注册商通知【'.WEBNAME.'】';
        $this->redisemail($userinfo['userid'],$userinfo['email'],$title,$txt);
    }
    // 资金冻结
    // params 用户ID 发送用户 冻结金额 剩余金额 冻结说明
    public function freezing($userid,$uid,$mot,$money,$mm,$sm){
        $txtt="尊敬的怀米网用户，您好！<br> 您的余额已被冻结:{$money}元<br>可用金额:{$mm}<br>冻结原因：{$sm}!<br>如果您对本次通知内容、操作过程有任何疑问，请<a href='".WEBURL."user/#/user/help/workorder'>提交工单</a>进行反馈。<br>";
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '资金冻结通知';
        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
        $aa = new SemdMot();
        $res = $aa->yjsendsms($mot,$money,$uid,'SMS_172170461');
        if($res['Message'] == 'OK'){
            Db::name('domain_sendlog')->insert(['uip' => $_SERVER['REMOTE_ADDR'],'sendtime' => time(),'type' => 6,'userid' => $userid]);
        }
    }
    // 解冻
    public function unfreezing($info){
        $txtt="尊敬的怀米网用户，您好！<br>您因为:<span style='color:red'>{$info['tit']}</span>冻结{$info['moneynum']}元的保证金已经还原,请及时去用户控制台查看!<br>如果您对本次通知内容、操作过程有任何疑问，请<a href='".WEBURL."user/#/user/help/workorder'>提交工单</a>进行反馈。<br>";
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '资金还原通知';
        $this->redisemail($info['userid'],$info['uid'],$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
        $aa = new SemdMot();
        $res = $aa->yjsendsms($info['mot'],$info['moneynum'],$info['uid'],'SMS_172170466');
        if($res['Message'] == 'OK'){
            Db::name('domain_sendlog')->insert(['uip' => $_SERVER['REMOTE_ADDR'],'sendtime' => time(),'type' => 6,'userid' => $info['userid'] ]);
        }
      
    }
    //保证金扣款
    public function deductfreezing($info){
        $txtt="尊敬的怀米网用户，您好！<br>您因为:<span style='color:red'>{$info['tit']}</span>冻结{$info['moneynum']}元的保证金已经被系统扣除!<br>如果您对本次通知内容、操作过程有任何疑问，请<a href='".WEBURL."user/#/user/help/workorder'>提交工单</a>进行反馈。<br>";
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '资金扣除通知';
        $this->redisemail($info['userid'],$info['uid'],$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
//        $aa = new SemdMot();
//        $res = $aa->yjsendsms($info['mot'],$info['moneynum'],$info['uid'],'SMS_172170466');
//        if($res['Message'] == 'OK'){
//            Db::name('domain_sendlog')->insert(['uip' => $_SERVER['REMOTE_ADDR'],'sendtime' => time(),'type' => 10,'userid' => $info['userid'] ]);
//        }
    }
    // 提现
    // params 用户ID 用户名 提取金额  实际金额 成功/失败 失败说明
    public function withdraw($userid,$uid,$money1,$resultmoney,$type='succ',$ms = ''){
        $sj=date("Y-m-d H:i:s");
        $s1 = "<hr>此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>)查看。";
        $sxm = $money1-$resultmoney;
        if($type == 'succ'){
            $str="您的提现申请已经成功通过，请注意查收款项。<hr>提现金额：{$money1}元<br>手续费：{$sxm}元<br>应到金额：{$resultmoney}元<br>处理时间：$sj";
        }else{
            $str = "很遗憾,您的提现申请未通过<br>原因：".$ms.'<br>处理时间:'.$sj;
        }
        $title = '提现处理通知';
        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$str.$s1,$title,$str,46);
    }
    // 域名转入 发送失败邮件
    // params 用户ID 发送用户 失败说明 标识批次
    public function ingeinto($userid,$uid,$remark,$flag){
        
        $txtt = "尊敬的怀米网用户，您好！<br> 您申请的域名转入 批次:<span style='color:red'>{$flag}</span> 审核失败;<br>失败原因：<span style='color:red'>{$remark}</span><br>如果您对本次通知内容、操作过程有任何疑问，请<a href='".WEBURL."user/#/user/help/workorder'>提交工单</a>进行反馈。<br>";
       
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '域名转入失败';
        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
    }

    //单个域名预定通知
    public function domainReserve($info,$status){
        $uid = Db::name('domain_user')->where('id',$info['userid'])->value('uid');
        if($status == 1){
            $text = "您在怀米网预定的域名: <span style='color:green'>{$info['tit']}</span> 预定域名成功,请去<a href='".WEBURL."user#/user/mydomain/index'>我的域名</a>进行查下!";
        }elseif($status == 2){
            $text = "您在怀米网预定的域名: <span style='color:green'>{$info['tit']}</span> 由于多人预定已转入竞拍阶段,竞拍时间:".date('Y-m-d H:i:s',$info['start_time'])." —— ".date('Y-m-d H:i:s',$info['end_time']);
        }else{
            $text = "您在怀米网预定的域名: <span style='color:red'>{$info['tit']}</span> 预定失败";
        }
        $txtt = "尊敬的怀米网用户，您好！<br> ".$text." <br>如果您对本次通知内容、操作过程有任何疑问，请<a href='".WEBURL."user/#/user/help/workorder'>提交工单</a>进行反馈。<br>";
       
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '域名预定通知';
        $this->redisemail($info['userid'],$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
    }
    //批量订单预定通知
    public function betchDomainReserve($uid,$data,$status){
        $len = count($data);
        if($status == 3){ //处理失败
            $txtt = "尊敬的怀米网用户，您好！<br>您在本网站预定的<span style='color:red;'>" . $len . "</span>个域名预定失败!<br>稍后您可以登录怀米网--域名抢注--我预定的域名<a href='" . WEBURL . "user/#/user/reserve/mydomain'>查看详情</a>。<br>如果您对本次通知内容、操作过程有任何疑问，请<a href='" . WEBURL . "/user/#/user/help/workorder'>提交工单</a>进行反馈。<br>";
        }else{
            $txtt = "尊敬的怀米网用户，您好！<br>您在本网站预定的<span style='color:red;'>" . $len . "</span>个域名已转为竞拍状态!<br>稍后您可以登录怀米网--域名抢注--我参与的竞价<a href='" . WEBURL . "user/#/user/reserve/myauction'>查看详情</a>。<br>如果您对本次通知内容、操作过程有任何疑问，请<a href='" . WEBURL . "/user/#/user/help/workorder'>提交工单</a>进行反馈。<br>";
            $txtt .= "<table style='width:800px;border: 1px dashed gray;'><tr><th align='left'>编号</th><th align='left'>域名</th><th align='left'>竞拍开始时间</th><th align='left'>竞拍预计结束时间</th></tr>";
            foreach ($data as $k => $v) {
                if ($k >= 20) {
                    break;
                }
                $starTime = date('Y-m-d H:i:s', $v['start_time']);
                $endTime = date('Y-m-d H:i:s', $v['end_time']);
                $txtt .= "<tr><td align='center'>" . ($k + 1) . "</td><td align='left'>{$v['tit']}</td><td align='left'>{$starTime}</td><td align='left'>{$endTime}</td></tr>";
            }
            if ($len > 20) {
                $txtt .= "<tr><td colspan='4' align='left'><span style='font-size:23px;margin-left:88%;'><a href='" . WEBURL . "/user/#/user/reserve/endauction'>查看更多</a></span></td></tr>";
            }
            $txtt .= "</table>";
        }
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '域名预定通知';
        $this->redisemail($data[0]['userid'],$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
    }

    /**
     * 注销账户 邮箱通知
     */
    public function cancelNotice($userid,$uid,$status,$remark){
        if($status == 1){
            $msg = '审核不通过';
        }else{
            $msg = '审核已通过,账号已经注销';
        }
        $remark = empty($remark) ? $msg : $remark;
        $txtt = "尊敬的用户，您好！<br> 您在怀米网申请 <span style='color: red;'>{$uid}</span> 的账号注销: <span style='color:red'>{$msg}</span>;<br>备注原因：<span style='color:red'>{$remark}</span><br><span style='color:green: ;'>如果您未在怀米网上注册过账户,请忽略该邮件！</span><br>如果您对本次通知内容、操作过程有任何疑问，请联系官网客服。<br>";
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '账号注销通知';
        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta);
    }


    /**
     * 提现承诺书 withdraw
     */
    public function withdrawPromise($userid,$uid,$txkh,$status,$remark){

        if($status == 1){
            $msg = '需要补充承诺信息:【 '.$remark.' 】请尽快去 用户中心-》财务管理-》提现记录: 当前记录右侧【承诺资料】里面提交!';
        }else{
            $msg = '【 '.$remark.' 】承诺信息未通过,需要去 用户中心-》财务管理-》提现记录: 当前记录右侧【承诺资料】查看原因及重新提交!';
        }
        
        $txtt = "尊敬的用户，您好！<br> 您在怀米网提现申请触发系统风控检测,提现账号/卡号: <span style='color: red;'>{$txkh}</span> <span style='color:red'>{$msg}</span>;<br><span style='color:green: ;'>如果您未在怀米网上注册过账户,请忽略该邮件！</span><br>如果您对本次通知内容、操作过程有任何疑问，请联系官网客服。<br>";
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '提现补充信息通知';

        $this->sendSiteMessage($userid,$title,$txtt,46);

        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
    }

    /**
     * 店铺账号操作邮件
     */
    public function shopStatus($userid,$uid,$status,$shopName,$remark=''){
        if($status == 1){ //正常使用
            $msg = '您的店铺 <span style="color:red;">'.$shopName.'</span> 已经恢复正常状态';
        }elseif($status == 3) { //禁用
            $msg = '您的店铺 <span style="color:red;">'.$shopName.'</span> 已被禁止使用,具体原因请联系官网客服!';
        }else if($status == 4){ //拒绝申请
            $msg = '您店铺 <span style="color:red;">'.$shopName.'</span> 的开店申请已被拒绝,原因:<br><span style="color:red;"> '.$remark.'</span> !';
        }else if($status == 5){
            $msg = '您店铺 <span style="color:red;">'.$shopName.'</span> 的消保店铺申请已被拒绝,原因:<br><span style="color:red;"> '.$remark.'</span> !';
        }else if($status == 6){
            $msg = '您店铺 <span style="color:red;">'.$shopName.'</span> 的消保店铺已通过申请!';
        }
        
        $txtt = "尊敬的用户，您好！<br> {$msg} <br><span style='color:green: ;'>如果您未在怀米网上注册过账户,请忽略该邮件！</span><br>如果您对本次通知内容、操作过程有任何疑问，请联系官网客服。<br>";
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '店铺状态变更通知';
        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
    }

    /**
     * 店铺状态变更
     */
    /**
     * 店铺账号操作邮件
     */
    public function shopAccount($userid,$uid,$status,$account,$remark=''){
        if($status == 0){ //启用
            $msg = '您的店铺账号 <span style="color:red;">'.$account.'</span> 已恢复正常状态,可以去 店铺管理->店铺账号 设置默认属性';
        }elseif($status == 1) { //禁用
            $msg = '您的店铺账号 <span style="color:red;">'.$account.'</span> 已被禁止使用,原因:<br><span style="color:red;"> '.$remark.'</span> !<br>如果该店铺号为默认店铺号,默认店铺号则会发生重置!';
        }else{//删除
            $msg = '您的店铺账号 <span style="color:red;">'.$account.'</span> 已被手动删除,原因:<br><span style="color:red;"> '.$remark.'</span> !<br>如果该店铺号为默认店铺号,默认店铺号则会发生重置!';
        }

        $txtt = "尊敬的用户，您好！<br> {$msg} <br><span style='color:green: ;'>如果您未在怀米网上注册过账户,请忽略该邮件！</span><br>如果您对本次通知内容、操作过程有任何疑问，请联系官网客服。<br>";
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        $title = '店铺号状态通知';
        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
    }


    /**
     * 域名冻结
     */
    public function domainFrezee($userid,$uid,$status,$domains,$cause=''){
        if($status == 1){
            $msg = '域名因<span style="color:red">'.$cause.'</span>被冻结';
            $title = '域名冻结通知';
        }else{
            $msg = '域名解除冻结';
            $title = '域名解除冻结通知';
        }
        if(is_array($domains)){
            $domains = implode(',',$domains);
        }
        $txtt = '尊敬的用户，您好！您有以下'.$msg.':<br><span style="color:red">'.$domains.'</span><br>';
        $txtta = "此为系统邮件，请勿回复。来自怀米网 - 国内专业域名交易平台 - <a href='".WEBURL."' >".WEBURL."</a>，详情可登陆 (<a href='".WEBURL."user/'>管理中心</a>) 查看。";
        
        $this->redisemail($userid,$uid,$title.'【'.WEBNAME.'】',$txtt.$txtta,$title,$txtt,46);
    }

    /**
     * 发送站内消息
     */
    protected function sendSiteMessage($userid,$tit,$con,$type){

        $id = Db::name('domain_msg')->insertGetId([
            'tit' => $tit,
            'con' => $con,
            'create_time' => time(),
            'type' => $type,
            'all' => 0,
        ]);
        Db::table(PREFIX.'domain_msgStu')->insert([
            'cid' => $id,
            'userid' => $userid,
            'status' => 0,
        ]);

    }

    /*
    缓存邮箱队列，默认用1号库
    邮箱内容，其它的为消息字段
    */
    protected function redisemail($userid,$email,$title,$content,$msgtit = null,$msgcontent = null,$type = 0){
        $redis = new Redis(['select' => 1]);
        $time = time();
        $key = $time.rand(100000,999999).$userid;
        $redis->RPush('email_task_id',$key);//存入队列
        $redis->hMset('email_data_'.$key,['title' => $title,'content' => $content,'email' => $email,'msgtit' => $msgtit,'msgcontent' => $msgcontent,'type' => $type,'userid' => $userid,'time' => $time]);
         // echo '<pre>';
         // $test = $redis->lrange('email_task_id',0,-1);
         // foreach($test as $k => $v){
         //     print_r($redis->hgetall('email_data_'.$v));
         //     $redis->lrem('email_task_id',0,$v);
         //     $redis->del('email_data_'.$v);
         // }
         // die;
    }    
}
	