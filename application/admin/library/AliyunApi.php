<?php
namespace app\admin\library;
use DefaultAcsClient;
use DefaultProfile;
require_once '../vendor/aliyun/aliyun-php-sdk-core/Config.php';
use Domain\Request\V20180129\SaveTaskForUpdatingRegistrantInfoByRegistrantProfileIDRequest;
use Domain\Request\V20180129\SaveBatchTaskForModifyingDomainDnsRequest;
use Domain\Request\V20180129\SaveRegistrantProfileRequest;
use Domain\Request\V20180129\DeleteRegistrantProfileRequest;
use Domain\Request\V20180129\RegistrantProfileRealNameVerificationRequest;
use Domain\Request\V20180129\QueryRegistrantProfilesRequest;
use Domain\Request\V20180129\QueryFailReasonForRegistrantProfileRealNameVerificationRequest;
use Domain\Request\V20180129\SubmitEmailVerificationRequest;
use Domain\Request\V20180129\QueryContactInfoRequest;
use Domain\Request\V20180129\CheckDomainRequest;
use Domain\Request\V20180129\SaveBatchTaskForCreatingOrderActivateRequest;
use Domain\Request\V20180129\QueryDomainListRequest;
use Domain\Request\V20180129\QueryDomainByInstanceIdRequest;
use Alidns\Request\V20150109\AddDomainRecordRequest;
use Alidns\Request\V20150109\UpdateDomainRecordRequest;
use Alidns\Request\V20150109\DeleteDomainRecordRequest;
use Alidns\Request\V20150109\SetDomainRecordStatusRequest;
use Domain\Request\V20180129\QueryRegistrantProfileRealNameVerificationInfoRequest;
use Domain\Request\V20180129\QueryDomainByDomainNameRequest;

/**
 * 阿里云域名相关操作接口
 */
class AliyunApi
{
    protected $iClientProfile,$client;
    public function __construct($Region_ID,$AccessKey_ID,$Secret)
    {
        $this->iClientProfile = DefaultProfile::getProfile($Region_ID,$AccessKey_ID,$Secret);
        $this->client = new DefaultAcsClient($this->iClientProfile);
    }
    /**
     * 根据查询单个域名信息
     */
    public function querySingleDomainInfo($domain)
    {
        $request = new QueryDomainByDomainNameRequest();
        $request -> setDomainName($domain);
        $result = $this->json_result($request);
        return $result;
    }
    /**
     * 查询实名认证信息
     */
    public function QueryRegistrantProfileRealNameVerificationInfo($RegistrantProfileId){
        $request = new QueryRegistrantProfileRealNameVerificationInfoRequest();
        $request->setRegistrantProfileId($RegistrantProfileId);
        $request->setFetchImage(true);
        return $this->json_result($request);
    }
    
    /**
     * 添加编辑解析记录
     */
    public function addDomainRecord($domain,$rr,$type,$value,$ttl,$line,$recordid = 0){
        if($recordid){
            $request = new UpdateDomainRecordRequest();
            $request->setRecordId($recordid);
        }
        else{
            $request = new AddDomainRecordRequest();
            $request->setDomainName($domain);
        }
        $request->setRR($rr);
        $request->setType($type);
        $request->setValue($value);
        $request->setTTL($ttl);
        $request->setLine($line);
        return $this->json_result($request);
    }
    
    /**
     * 删除解析
     */
    public function deleteDomainRecord($recordid){
        $request = new DeleteDomainRecordRequest();
        $request->setRecordId($recordid);
        return $this->json_result($request);
    }
    
    /**
     * 更新解析记录状态
     */
    public function setDomainStatus($recordid,$Status){
        $request = new SetDomainRecordStatusRequest();
        $request->setRecordId($recordid);
        $request->setStatus($Status);
        return $this->json_result($request);
    }
    
    /**
     * 根据域名实例ID获取域名信息
     */
    public function QueryDomainByInstanceId($InstanceId){
        $request = new QueryDomainByInstanceIdRequest();
        $request->setInstanceId($InstanceId);
        return $this->json_result($request);
    }
    
    /**
     * 查询阿里云账户下域名
     */
    public function QueryDomainList($info){
        $request = new QueryDomainListRequest();
        $request->setPageNum($info['PageNum']);
        $request->setPageSize($info['PageSize']);
        if($info['OrderByType'])
            $request->setOrderByType($info['OrderByType']);
        if($info['DomainName'])
            $request->setDomainName($info['DomainName']);
        return $this->json_result($request);
    }
    
    /*
     * 批量注册域名
     * */
    public function SaveBatchTaskForCreatingOrderActivate($info){
        $request = new SaveBatchTaskForCreatingOrderActivateRequest();
        $request->setOrderActivateParams($info);
        return $this->json_result($request);
    }
    
    /**
     * 查询域名是否可以注册
     */
    public function CheckDomain($DomainName){
        $request = new CheckDomainRequest();
        $request->setDomainName($DomainName);
        return $this->json_result($request);
    }
    
    /**
     * 查询域名联系人信息
     */
    public function QueryContactInfo($domain){
        $request = new QueryContactInfoRequest();
        $request->setDomainName($domain);
        $request->setContactType('registrant');
        return $this->json_result($request);
    }
    
    /**
     * 创建信息模板
     */
    public function SaveRegistrantProfile($info){
        $request = new SaveRegistrantProfileRequest();
        if(isset($info['RegistrantProfileId']))
            $request->setRegistrantProfileId($info['RegistrantProfileId']);
        if(isset($info['Telephone']))
            $request->setTelephone($info['Telephone']);
        $request->setDefaultRegistrantProfile(false);
        if(isset($info['Country']))
            $request->setCountry($info['Country']);
        if(isset($info['Province']))
            $request->setProvince($info['Province']);
        if(isset($info['TelArea']))
            $request->setTelArea($info['TelArea']);
        if(isset($info['City']))
            $request->setCity($info['City']);
        if(isset($info['PostalCode']))
            $request->setPostalCode($info['PostalCode']);
        if(isset($info['Email']))
            $request->setEmail($info['Email']);
        if(isset($info['Address']))
            $request->setAddress($info['Address']);
        if(isset($info['RegistrantName']))
            $request->setRegistrantName($info['RegistrantName']);
        if(isset($info['RegistrantOrganization']))
            $request->setRegistrantOrganization($info['RegistrantOrganization']);
        if(isset($info['TelExt']))
            $request->setTelExt($info['TelExt']);
        if(isset($info['ZhRegistrantOrganization']))
            $request->setZhRegistrantOrganization($info['ZhRegistrantOrganization']);
        if(isset($info['ZhRegistrantName']))
            $request->setZhRegistrantName($info['ZhRegistrantName']);
        if(isset($info['ZhProvince']))
            $request->setZhProvince($info['ZhProvince']);
        if(isset($info['ZhAddress']))
            $request->setZhAddress($info['ZhAddress']);
        if(isset($info['ZhCity']))
            $request->setZhCity($info['ZhCity']);
        $request->setRegistrantType(1);
        $request->setLang('zh');
        return $this->json_result($request);
    }
    
    /**
     * 删除信息模板
     */
    public function DeleteRegistrantProfile($RegistrantProfileId){
        $request = new DeleteRegistrantProfileRequest();
        $request->setRegistrantProfileId($RegistrantProfileId);
        return $this->json_result($request);
    }
    
    /**
     * 信息模板实名认证
     */
    public function RegistrantProfileRealNameVerification($info){
        $request = new RegistrantProfileRealNameVerificationRequest();
        $request->setRegistrantProfileID($info['RegistrantProfileID']);
        $request->setIdentityCredentialNo($info['IdentityCredentialNo']);
        $request->setIdentityCredentialType($info['IdentityCredentialType']);
        $request->setIdentityCredential($info['IdentityCredential']);
        return $this->json_result($request);
    }
    
    /**
     * 信息模板实名认证
     */
    public function RegistrantProfileRealNameVerification11($info){
        $request = new RegistrantProfileRealNameVerificationRequest();
        $request->setRegistrantProfileID(6618054);
        $request->setIdentityCredentialNo($info['renzhengno']);
        $request->setIdentityCredentialType('SFZ');
        $request->setIdentityCredential($info['base64']);
        return $this->json_result($request);
    }
    
    /**
     * 查询信息模板
     */
    public function QueryRegistrantProfiles($info){
        $request = new QueryRegistrantProfilesRequest();
        $request->setRegistrantProfileId($info['RegistrantProfileId']);
        return $this->json_result($request);
    }
    
    /**
     * 查询信息模板的实名认证状态
     */
    public function QueryFailReasonForRegistrantProfileRealNameVerification($RegistrantProfileID){
        $request = new QueryFailReasonForRegistrantProfileRealNameVerificationRequest();
        $request->setRegistrantProfileID($RegistrantProfileID);
        return $this->json_result($request);
    }
    
    /**
     * 阿里云邮箱验证接口
     */
    public function SubmitEmailVerification($info){
        $request = new SubmitEmailVerificationRequest();
        $request->setEmail($info['Email']);
        $request->setSendIfExist($info['SendIfExist']);
        return $this->json_result($request);
    }
    
    /**
     * 域名模板过户
     */
    public function SaveTaskForUpdatingRegistrantInfoByRegistrantProfileID($domain,$infoid){
        $request = new SaveTaskForUpdatingRegistrantInfoByRegistrantProfileIDRequest();
        $request->setDomainNames($domain);
        $request->setRegistrantProfileId($infoid);
        return $this->json_result($request);
    }
    
    /**
     * 批量修改DNS
     */
    public function SaveBatchTaskForModifyingDomainDns($domain,$dns,$isaliyun){
        $request = new SaveBatchTaskForModifyingDomainDnsRequest();
        $request->setDomainNames($domain);
        if($isaliyun)
            $request->setAliyunDns(true);
        else
            $request->setAliyunDns(false);
        if($dns)
            $request->setDomainNameServers($dns);
        $request->setLang('zh');
        return $this->json_result($request);
    }
    
    /**
     * 解析接口返回结果
     */
    public function json_result($request){
        $response = $this->client->getAcsResponse($request);
        $response = json_encode($response);
        $response = json_decode($response,true);
        if(!isset($response['result']))
            $response['result'] = 0;
        return $response;
    }
}
