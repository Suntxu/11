<?php
namespace app\admin\common;
set_time_limit(0);
/**
 * 发送验证码
 */
class SemdMot
{
    private $accessKeyId = "LTAIFvR702GIR5rc";
    private $accessKeySecret = "41zVTPa7Ie25K0TyyV80d0pNNTsMwh";

     public function __construct(){
        
    }
    public function yjsendsms($m1,$m2,$m3,$m = null){
        if(!empty($m1)){
            $params = array ();
            $params["PhoneNumbers"] = $m1;
            $params["SignName"] = "怀米网";
            $params["TemplateCode"] = $m;
            $params['TemplateParam'] = Array (
                 "code" => $m2,
                 'name' => $m3,
             );
            if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
                $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
            }
            //$helper = new SignatureHelper();
            $content = $this->request(
                $this->accessKeyId,
                $this->accessKeySecret,
                "dysmsapi.aliyuncs.com",
                array_merge($params, array(
                    "RegionId" => "cn-hangzhou",
                    "Action" => "SendSms",
                    "Version" => "2017-05-25",
                ))
            );
            
            return $content;
        }
    return 0;
}

/**
     * 生成签名并发起请求
     *
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @param $security boolean 使用https
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    public function request($accessKeyId, $accessKeySecret, $domain, $params, $security=false) {
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }

        $stringToSign = "GET&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));

        $signature = $this->encode($sign);

        $url = ($security ? 'https' : 'http')."://{$domain}/?Signature={$signature}{$sortedQueryStringTmp}";

        try {
            $content = $this->fetchContent($url);
            return json_decode($content,true);
        } catch( \Exception $e) {
            return false;
        }
    }

    public function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    public function fetchContent($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));

        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $rtn = curl_exec($ch);

        if($rtn === false) {
            trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
        }
        curl_close($ch);

        return $rtn;
    }
  
}


?>