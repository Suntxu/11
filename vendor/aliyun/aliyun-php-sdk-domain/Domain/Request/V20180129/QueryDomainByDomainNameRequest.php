<?php
namespace Domain\Request\V20180129;
/**
 * @deprecated Please use https://github.com/aliyun/openapi-sdk-php
 *
 * Request of QueryDomainByDomainName
 *
 * @method string getUserClientIp()
 * @method string getDomainName()
 * @method string getLang()
 */
class QueryDomainByDomainNameRequest extends \RpcAcsRequest
{
    /**
     * @var string
     */
    protected $method = 'POST';
    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'Domain',
            '2018-01-29',
            'QueryDomainByDomainName'
        );
    }
    /**
     * @param string $userClientIp
     *
     * @return $this
     */
    public function setUserClientIp($userClientIp)
    {
        $this->requestParameters['UserClientIp'] = $userClientIp;
        $this->queryParameters['UserClientIp'] = $userClientIp;
        return $this;
    }
    /**
     * @param string $domainName
     *
     * @return $this
     */
    public function setDomainName($domainName)
    {
        $this->requestParameters['DomainName'] = $domainName;
        $this->queryParameters['DomainName'] = $domainName;
        return $this;
    }
    /**
     * @param string $lang
     *
     * @return $this
     */
    public function setLang($lang)
    {
        $this->requestParameters['Lang'] = $lang;
        $this->queryParameters['Lang'] = $lang;
        return $this;
    }
}