<?php
/**
 * 后台核心函数库1
 */
namespace app\admin\common;
use think\Db;
use think\Validate;
use app\admin\library\Redis;
use fast\Http;
use think\Config;

class Fun 
{
	static $obj;
	private function __construct(){
	}
	static public function ini()
	{
		if(!self::$obj){
			self::$obj = new self();
		}
		return self::$obj;
	}
	/**
	 * 日期转化方法
	 * params： 日期或者时间戳,如果为真 时间戳--》日期 else 时间戳 《--日期
	 * returns: 转化的数据 
	 * author:Mrlu
	 */
	public function DateToTime($data,$flag=true)
	{
		if($data){
			if(is_array($data)){
				$msg = [];
				if($flag){
					foreach($data as $k=>$v){
						if($v){
							$msg[$k] = strtotime($v);  
						}else{
							$msg[$k] = '';  
						}
					}
				}else{
					foreach($data as $k=>$v){
						if($v){
							$msg[$k] = date('Y-m-d H:i:s',$v);  
						}else{
							$msg[$k] = ''; 
						}
					}
				}
				return $msg;
			}else{
				if($flag){
					return date('Y-m-d H:i:s',$data);
				}else{
					return strtotime($data);
				}
			}
		}
		return '';
	}
	/**
	 * 生成唯一随机数
	 * params： 随机长度
	 * returns: 生成的随机数
	 * author:Mrlu
	*/
	public function rands($len=16)
	{
		$yz = range(1,$len);
		$aa = '';
		$sj = time();
		$zimu = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',0,1,2,3,4,5,6,7,8,9,'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
		for($i=0;$i<$len;$i++){
			shuffle($zimu);
			$aa .= $zimu[array_rand($yz)];
		}
		return $aa;
	}
	/**
	 * 获取状态
	 * params：shuzhi  array  不查数据库
	 * returns: 数字对应的状态
	*/
	public function getStatus($n,$set)
	{	
		return isset($set[$n]) ? $set[$n] : '';
	}
	/**
	 * 返回截取的字符串
	 * params：str len
	 * returns: 应的值
	*/
	public function returntitdian($t,$l,$coding = 'utf-8')
	{
        $len = mb_strlen($t,$coding);
        if($len > $l){
            $t = mb_substr( $t , 0 , $l ).'...';
        }
        return $t;
	}
//	/**
//	 * 获取??单表数据
//	 * params：表名 条件 字段 id
//	 * returns: 查找的数据
//	*/
//	public function getList($tableName,$Where,$Fleid='*',$id='')
//	{
//		if($id){
//			$data = Db::table($tableName)->where(['id'=>$id])->field($Fleid)->find();
//			return $data[$Fleid];
//		}
//		return Db::table($tableName)->where($Where)->field($Fleid)->select();
//	}
//	/**
//	 * 插入数据表
//	 * params 表名 数据
//	 */
//	public function insertTab($tab,$data){
//		if(is_array($data) && $tab){
//			if (count($data) == count($data, 1)){
//				return Db::table($tab)->insert($data);
//			}else{
//				return Db::table($tab)->insertAll($data);
//			}
//		}
//		return '缺少重要参数';
//	}

	//图片转base64
    public function base64EncodeImage($filename,$flag=true){
    	// $filename = IMGURL.$filename;
        // $image_info = getimagesize($filename);
        if($flag){
        	$filename.='?da='.rand(1000,9999);
        }
        return base64_encode(file_get_contents($filename));
    }
    
     /**
     * 查询 whois 信息---站长之家页面元素
     * auther Mrlu
     * @param String tit : 域名
     * @return Array 查询的结果集
     */
    public function getWhois($tit){
        $url = 'http://whois.chinaz.com/?Domain='.$tit.'&isforceupdate=1&ws=';
        $file = file_get_contents($url);
        preg_match_all('/<div class="fl WhLeList-left">(.*?)<\/div>/i',$file,$res); //左侧
        preg_match_all('/<div class="fr WhLeList-right.*?">(.*?)<\/div>/i',$file,$res1); //右侧
        $arr = [];
        if(empty($res[1][2])){
            return $arr; //已隐藏
        }
        foreach($res[1] as $k=>$v){
            if($v == 'DNS'){
            	$arr[$v] = preg_replace('/<br\/>/',' ',$res1[1][$k]);
            }else{
            	$arr[$v] = str_replace('[whois反查]','',strip_tags($res1[1][$k]));
            }
            
        }
       return $arr;
    }
    /**
     * 将多行输入切割成数组，并去掉重复元素
     */
    public function moreRow($domain){
        $domain = preg_replace('# #','',$domain);
        $av = str_replace(["\r"," "],"",$domain);
        $domainlist = preg_split("/\n/",$av);
        return array_map('trim',array_unique(array_filter($domainlist)));
    }
    /**
     * 转化解析路线
     */
    public static function getLinestr($status){
        $line = ['default' => '默认','unicom' => '联通','telecom' => '电信','mobile' => '移动','edu' => '中国教育网','oversea' => '境外','baidu' => '百度','biying' => '必应','google' => '谷歌'];
        return $line[$status];
    }
	//获取域名的注册/到期时间
//    public function getDomainExpire($domain){
//        $url = 'http://cha-157.huaimi.com/index/tool/whoistimes?domain='.$domain;
//        $res = Http::sendRequest($url);
//        if($res['ret']){
//            $res = json_decode($res['msg'],true);
//            if($res['status'] == 1){
//                return $res;
//            }
//        }
//        return false;
//    }
    
    /** 获取域名类型
     * @return array
     */
    public function getDomainType(){
        return [
            'sz' => ['纯数字',['sz1sz' => '单数字','sz2sz' => '二数字','sz3sz' => '三数字','sz4sz' => '四数字','sz5sz' => '五数字','sz6sz' => '六数字','sz7sz' => '七数字','sz8sz' => '八数字','sz9sz' => '九数字']],
            'zm' => ['纯字母',['zm1zm' => '单字母','zm2zm' => '二字母','zm3zm' => '三字母','zm4zm' => '四字母','zm5zm' => '五字母','zm6zm' => '六字母','sm' => '纯声母','sm2sm' => '双声母','sm3sm' => '三声母','sm4sm' => '四声母','sm5sm' => '五声母']],
            // 'py' => ['拼音',['py1py' => '单拼','py2py' => '双拼','py3py' => '三拼','py4py' => '四拼']],
            'za' => ['杂米',['za2za' => '二杂','za3za' => '三杂','za4za' => '四杂','za5za' => '五杂']],
            'zw' => ['特殊',[]],
        ];
    }

    /**
     * 请求纳点网api
     * @param  Array  api 	api信息
     * @param  Int 	  time 	时间戳
     * @param  String cmd   请求路径 
     * @param  String method请求方法
     * @return Array 查询的结果集或false
     */
    public function requestNadianApi($api,$time,$cmd,$data,$method){

		$signature = strtolower(md5($api['accessKey'].strtolower(md5($api['secret'])).$time.$api['region']));
		$data['signature'] = $signature;
		$data['member_id'] = $api['accessKey'];
		$data['timestamp'] = $time;
		$result = Http::sendRequest($api['api'].$cmd,$data,$method);
		// echo '<pre>';
		// print_r($result);
		// die;
		if($result['ret'])
			return json_decode($result['msg'],true);
		return false;
	}
	/**
	 * 等比例裁剪图像大小
	 * @param  [type] 图像路径
	 * @param  [type] $xmax    宽
	 * @param  [type] $ymax    高
	 * @return [type] false  符合大小 true 裁剪成功         
	 */
	public function resizeImage($tmpname, $xmax = 2000, $ymax = 2000,$size = 56320)
	{
	    $im = imagecreatefromjpeg($tmpname);
	    $x = imagesx($im);
	    $y = imagesy($im);
		
	    if($x <= $xmax && $y <= $ymax)
	        return $this->base64EncodeImage($tmpname);
	 
	    if($x >= $y) {
	        $newx = $xmax;
	        $newy = $newx * $y / $x;
	    }
	    else {
	        $newy = $ymax;
	        $newx = $x / $y * $newy;
	    }
	    
	    $im2 = imagecreatetruecolor($newx, $newy);
	    imagecopyresized($im2, $im, 0, 0, 0, 0, floor($newx), floor($newy), $x, $y);
	    if($im2){
			$tmpfile = 'uploads/tem/'.uniqid().'.jpg';
			imagejpeg($im2, $tmpfile);
			$lsize = filesize($tmpfile);
			if($lsize < $size){//不处理
				return false;
			}
			else{//转换base64保存
				$base = $this -> base64EncodeImage($tmpfile,false);
				@unlink($tmpfile);
				return $base;
			}	    	
	    }

	    return false; 
	}

	/**
	 * 下载csv格式文件
	 * @param  [type] $header   头部
	 * @param  [type] $contet   内容 二维数组
	 * @param  string $filename 文件名
	 * @return [type]           [description]
	 */
	public function csvFile($header,$contet,$filename=''){

		$filename = empty($filename) ? time().'.csv' : $filename;

        mb_convert_variables('GB2312','ASCII,UTF-8',$header);

		header('Content-Type: application/vnd.ms-excel');   //header设置
        header("Content-Disposition: attachment;filename=".$filename);
        header('Cache-Control: max-age=0');
        
        $fp = fopen('php://output','a');
        
        fputcsv($fp,$header);

        foreach($contet as $k => $v){
        	$row = array_map(function($n){ return iconv('UTF-8','GB2312//IGNORE',$n); }, $v);
            fputcsv($fp,$row);
            unset($row);
        }

        fclose($fp);
        die;

	}
	/**
	 * 下载txt格式文件
	 * @param  [type] $contet   [description]
	 * @param  string $filename [description]
	 * @param  array  $header   [description]
	 * @return [type]           [description]
	 */
	public function txtFile($contet,$filename="",$header = []){

		$filename = empty($filename) ? date('YmdHis').'.txt' : $filename;
		header("Content-type: application/octet-stream");
		header("Accept-Ranges: bytes"); 
		header("Content-Disposition: attachment; filename = ".$filename); //文件命名
		header("Expires: 0"); 
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
		header("Pragma: public");
		//最大格式长度
		$len = 1;
		if(is_array($contet[0])){
			foreach($contet as $k => $v){
				foreach($v as $vv){
					$nlen = strlen($vv);
					$len = ($len > $nlen) ? $len : $nlen;
				}
			}	
		}
		$len += 1;
		if($header){
			foreach($header as $v){
				echo iconv('UTF-8','GB2312//IGNORE',str_pad($v,($len*1.2),' '));
			}
			echo "\n";
		}
		if(is_array($contet[0])){
			foreach($contet as $v){
				foreach($v as $vv){
					echo iconv('UTF-8','GB2312//IGNORE',str_pad($vv,$len,' '));
				}
				echo "\n";
			}
		}else{
			foreach($contet as $v){
				echo iconv('UTF-8','GB2312//IGNORE',$v)."\n";
			}
		}		
		die;
	}

    /**
     * 下载word文档
     */
    public function wordFile($contet,$filename=""){

        $filename = empty($filename) ? uniqid().'.doc' : $filename;

        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=$filename");

        $html = '<html xmlns:v="urn:schemas-microsoft-com:vml"
                xmlns:o="urn:schemas-microsoft-com:office:office"
                xmlns:w="urn:schemas-microsoft-com:office:word" 
                xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" 
                xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8" /></head>';

        echo $html . '<body>'.$contet .'</body></html>';
        die;

    }

	/**
	 * 查询域名状态
	 */
	public function queryDomainStatus($tit){
		//改成命令行检测
		exec("nslookup ".$tit, $output);
		$output = array_filter($output);
		$status = end($output);
        if (strpos($status, "NXDOMAIN") !== false){
            // 解析记录不存在
            return ['code' => '-1', 'msg'=>'serverhold'];
        }else{
            return ['code' => '1'];
        }
	}
	
	/**
	 * 获取多通道域名预定数量
	 */
	public function getMultDomainReserveNum(){

		$tits = [];
		$redis = new Redis(['select' => 6]);
		$regid = Config::get('mult_domain_reserve_zcs_id');
		$regid = array_keys($regid);
        foreach($regid as $v){
            $task = $redis->lrange('reg_going_reserve_'.$v,0,-1);
            foreach($task as $vv){
                $data = $redis->hgetall('reg_going_reserve_'.$v.'_'.$vv);
                if($data){
                    $domain = json_decode($data['domain'],true);
                    $api = json_decode($data['api'],true);
                    foreach($domain as $info){

                    	$skey = $info['id'].'_'.$api['id'];
                        if(isset($tits[$skey])){  //同一个注册商只显示一个域名
                            continue;
                        }
                        // //判断是否已经处理
                        $opk = $redis->get('book_submitsuccess_'.$api['id'].'_'.$info['tit']);
                        if( $opk){
                            continue;
                        }
                        $tits[$skey] = $info['tit'];
                    }
                }
            }
        }
        return count($tits);
	}

    /**
     * python 接口 token 生成
     */
    public function getPythonQueryToken($domain){

        return sha1('huaimi'.$domain.'FBIopenthedoor');

    }

    /*******************redis锁*******************/
    /**
     * 任意锁
     */
    public function lockKey($key,$expire = 5,$redis=null){

        if(empty($redis)){
            $redis = new Redis();
        }
        return $redis->lock($key,$expire);

    }
    public function unlockKey($key,$redis=null){

        if(empty($redis)){
            $redis = new Redis();
        }
        $redis->unlock($key);
        return true;
    }
    /**
     * 保证金锁
     */
    public function lockBaoMoney($userid,$expire = 10,$redis = null){
    	$key = 'bao_money'.$userid;
    	if(empty($redis)){
    		$redis = new Redis();
    	}
    	return $redis->lock($key);
    }

    public function unlockBaoMoney($userid,$redis=null){
        if(empty($redis)){
            $redis = new Redis();
        }
        $redis->unlock('bao_money'.$userid);
    	return true;
    }
    /**
     * 余额锁
     */
    public function lockMoney($userid,$redis = null,$expire = 10){
    	$key = 'buyer_money'.$userid;
    	if(empty($redis)){
    		$redis = new Redis();
    	}
        return $redis->lock($key);
    }

    public function unlockMoney($userid,$redis = null){
    	if(empty($redis)){
    		$redis = new Redis();
    	}
        $redis->unlock('buyer_money'.$userid);
    	return true;
    }
    /*
	 *同时锁账户余额与冻结资金
	*/
	public function lockFreezing($userid,$expire = 10,$redis = null){
		if(empty($redis)){
			$redis = new Redis();
		}
		$buyer_key = 'buyer_money'.$userid;
        $block = $redis->lock($buyer_key);
        if(!$block){
            return false;
        }
    	$bao_key = 'bao_money'.$userid;
        $llock = $redis->lock($bao_key);
        if(!$llock){
            $redis->unlock($buyer_key);
            return false;
        }
    	return true;
	}

    /*
     *解锁账户余额与冻结资金
    */
	public function unlockFreezing($userid,$redis = null){
		if(empty($redis)){
			$redis = new Redis();
		}
        $redis->unlock('buyer_money'.$userid);
        $redis->unlock('bao_money'.$userid);

	}
	/*******************end redis锁*******************/
}