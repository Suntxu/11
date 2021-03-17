<?php

namespace app\admin\library;

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 阿里云oss存储接口
 */
class OssApi 
{

	private $ossClient = NULL;

	public function __construct()
	{
		try{

			$this->ossClient = new OssClient(OSS_ACCESS_KEY_ID, OSS_ACCESS_KEY_Secret, OSS_POINT);

		}catch(OssException $e){

			die(json_encode(['code' => 1,'msg' => $e->getMessage()]));

		}

	}

	/**
	 * 获取所有bucket
	 */
	public function getBucketList()
	{
		$res = $this->ossClient->listBuckets();
		$bucketList = $res->getBucketList();
		$buckets = [];
		foreach($bucketList as $bucket) {
			$buckets[] = [
				'Location' => $bucket->getLocation(),
				'Name'	   => $bucket->getName(),
				'CreateDate' => $bucket->getCreatedate(),
			];
		}
		return ['code' => 0,'data' => $buckets ];

	}
 
	/**
	 * 设置bucket的权限
	 * @param  存储空间名字
	 * @param  权限 1 私有 2 公共读
	 */
	public function setBucketAuth($bucket,$type = 1)
	{
		$acl = ($type == 1) ? OssClient::OSS_ACL_TYPE_PRIVATE : OssClient::OSS_ACL_TYPE_PUBLIC_READ;
		try{
			$this->ossClient->putBucketAcl($bucket, $acl);
		}catch(OssException $e){
			return ['code' => 1,'msg' => $e->getMessage()];
		}
		return ['code' => 0,'msg' => '权限设置成功'];
	}

	/**
	 * 简单上传文件
	 * @param  存储空间名字
	 * @param  文件对象名字
	 * @param  文件路径或者字符串
	 * @param  上传类型 1 文件 2 字符串
	 * @return [type]          [description]
	 */
	public function uploadFile($bucket, $filename, $file,$type = 1)
	{
		
		try{
			if($type == 1){
				$res = $this->ossClient->uploadFile($bucket, $filename, $file);
			}else{
				$res = $this->ossClient->putObject($bucket, $filename, $file);
			}
		}catch(OssException $e){

			return ['code' => 1,'msg' => $e->getMessage()];

		}

		return ['code' => 0, 'path' => $filename ];

	}
	/**
	 * 删除文件
	 * @param  存储空间名
	 * @param  文件名
	 * @return [type]           [description]
	 */
	public function deleteField($bucket,$filename)
	{	
		try{
			if(is_array($filename)){ // 删除多个文件 一维数组
				$res = $this->ossClient->deleteObjects($bucket, $filename);
			}else{//单个文件
				$res = $this->ossClient->deleteObject($bucket, $filename);
			}
		}catch(OssException $e){
			return ['code' => 1,'msg' => $e->getMessage()];
		}
		return ['code' => 0, 'msg' => 'ok' ];
	}

	/**
	 * 列举文件
	 * @param  存储空间名字
	 * @param  文件前缀
	 * @param  数量
	 * @return [type]          [description]
	 */
	public function getObjectList($bucket,$prefix,$num = 1)
	{
		
		$options = ['prefix' => $prefix,'max-keys' => $num];
		$listObjectInfo = $ossClient->listObjects($bucket, $options);
		return $listObjectInfo;

		// $objectList = $listObjectInfo->getObjectList(); // object list
		// $prefixList = $listObjectInfo->getPrefixList(); // directory list


	}


}