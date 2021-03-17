<?php


namespace mailphp;

use think\Db;
use mailphp\PHPMailer;

/**
 * 发送邮件
 */
class SendM
{
	private $fname = null;  //标题
	private $tmail = null; //收件人
	private $str = null;	//内容
	private $info = [];     //邮件配置信息

	function __construct($fname='',$tmail='',$str='')
	{
		$this->fname = $fname;
		$this->tmail = $tmail;
		$this->str = $str;
		$mail = Db::name('domain_control')->field('mailtxt')->find(1);
		$this->info = preg_split('/,/',$mail['mailtxt']);
		return $this;
	}

	public function SendMail()
	{
		//开始发送
		$mail = new PHPMailer();
		$mail->IsHTML() ;//
		$mail->IsSMTP(); // 使用SMTP方式发送  
		$mail->Host     = $this->info[2]; //您的企业邮局域名
		$mail->SMTPAuth = true; // 启用SMTP验证功能 
		$mail->Username = $this->info[0]; // 邮局用户名(请填写完整的email地址)  
		$mail->Password = $this->info[1]; // 邮局密码  
		$mail->Port     = $this->info[3]; //接口
 		$mail->From     = $this->info[0]; //邮件发送者email地址 
 		$mail->FromName = $this->fname;
 		$mail->CharSet  = "utf8";
 		$mail->Subject  = $this->fname; //邮件标题
 		$mail->Body     = $this->str; //邮件内容
		$mail->AltBody  = ""; //附加信息，可以省略
		$mail->AddAddress($this->tmail,$this->tmail);//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")
		$mail->Send();
		// return $mail->ErrorInfo;

	}

}

?>