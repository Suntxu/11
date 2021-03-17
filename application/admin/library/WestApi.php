<?php

namespace app\admin\library;

use fast\Http;

class WestApi
{
    public $user,$password,$url,$cmd;
    public function __construct($user,$password)
    {
        $this->user = $user;
        $this->password = $password;
        $this->url = 'http://api.west263.com/api/';
    }
    
    /**
     * 西数api，接口命令、参数
     */
    public function westapi($config){
        $main_cmd = $this->building_cmd_string($config);
        $cmd = $this->cmd . $main_cmd;
        $versig = md5($this->user . $this->password . substr($cmd, 0, 10));
        $data = 'userid=' . $this->user . '&versig=' . $versig . '&strCmd=' . urlencode($cmd);
        $result = $this->http_curl($this->url, $data);
        $result2 =simplexml_load_string($result,'SimpleXMLElement',LIBXML_NOCDATA);
        $postObj = json_encode($result2);
        $arr = json_decode($postObj,true);
        return $arr;
    }
    /**
     * RRP接口
     */
    public function rrp(){
        $url = $this->url.'?s_login='.$this->user.'&s_pw='.$this->password.$this->cmd;
		
        $result = Http::sendRequest($url,[],'GET');
        if($result['ret']){
            $result = preg_split("/\n/",$result['msg']);
            $return = [];
            foreach($result as $v){
                $rv = explode('=',$v);
                $key = preg_replace('# #','',$rv[0]);
                if($key){
                    if($key == '[RESPONSE]' || $key == 'EOF')
                        continue;
                }
                $return[trim($rv[0])] = trim($rv[1]);
            }
            $return['ret'] = true;
            return $return;
        }
        return $result;
    }
    

    

    protected function http_curl($url, $data = '', $time_out = 30)
    {
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//设置链接
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//非零不自动返回
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 0);
        curl_setopt($ch ,CURLOPT_TIMEOUT , intval($time_out));//设置超时
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        //需要提交数据,则为POST方式
        if ($data){
            curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS , $data);//POST数据
        }
        //发送请求
        $response = curl_exec($ch);
        if($response === false)
        {
            throw new Exception('提交http请求失败: '. curl_error($ch));
        }
        curl_close($ch);
        return $response;
    }
    /**
     * 拼接西数参数
     */
    protected function building_cmd_string($array){
        $string = '';
        foreach($array as $key => $value)
        {
            //剔除空值参数
            if(strlen($value) > 0) $string .= "{$key}:".iconv("UTF-8", "GB2312//IGNORE", $value)."\r\n";
        }
        return "$string.\r\n";
    }
}
