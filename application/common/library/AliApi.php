<?php

namespace app\common\library;

require_once '../vendor/aliyun/aliyun-php-sdk-core/Config.php';

use DefaultAcsClient;
use DefaultProfile;
use Domain\Request\V20180129\QueryDomainByInstanceIdRequest; //根据实例查询域名信息
use Domain\Request\V20180129\QueryDomainListRequest;	//获取域名列表

/**
 * 阿里云调用接口
 */
class AliApi
{
	protected $iClientProfile,$client;
    public function __construct($Region_ID,$AccessKey_ID,$Secret)
    {
        $this->iClientProfile = DefaultProfile::getProfile($Region_ID,$AccessKey_ID,$Secret);
        $this->client = new DefaultAcsClient($this->iClientProfile);
    }
    //根据实例查询域名信息
	public function getDomainInfo($InstanceId){
		$request = new QueryDomainByInstanceIdRequest();
	    $request->setInstanceId($InstanceId);
	  	return $this->Response($request);
	}

	// 获取域名实例ID
	function QueryDomainList($info){
	    $request = new QueryDomainListRequest();
	    $request->setPageNum($info['PageNum']);
	    $request->setPageSize($info['PageSize']);
	    if(isset($info['OrderByType']))
	        $request->setOrderByType($info['OrderByType']);
	    if($info['DomainName'])
	        $request->setDomainName($info['DomainName']);
	    return $this->Response($request);
	}

	// 返回值
	public function Response($request){
		$response = $this->client->getAcsResponse($request);
        $response = json_encode($response);
        $response = json_decode($response,true);
        if(!isset($response['result']))
            $response['result'] = 0;
        return $response;
	}


}