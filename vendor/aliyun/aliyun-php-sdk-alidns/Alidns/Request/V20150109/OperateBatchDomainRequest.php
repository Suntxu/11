<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */
namespace Alidns\Request\V20150109;

class OperateBatchDomainRequest extends \RpcAcsRequest
{
	function  __construct()
	{
		parent::__construct("Alidns", "2015-01-09", "OperateBatchDomain");
		$this->setMethod("POST");
	}

	private  $userClientIp;

	private  $DomainRecordInfos;

	private  $lang;

	private  $type;

	public function getUserClientIp() {
		return $this->userClientIp;
	}

	public function setUserClientIp($userClientIp) {
		$this->userClientIp = $userClientIp;
		$this->queryParameters["UserClientIp"]=$userClientIp;
	}

	public function getDomainRecordInfos() {
		return $this->DomainRecordInfos;
	}

	public function setDomainRecordInfos($DomainRecordInfos) {
		$this->DomainRecordInfos = $DomainRecordInfos;
		for ($i = 0; $i < count($DomainRecordInfos); $i ++) {	
			$this->queryParameters['DomainRecordInfo.' . ($i + 1) . '.Rr'] = $DomainRecordInfos[$i]['Rr'];
			$this->queryParameters['DomainRecordInfo.' . ($i + 1) . '.Domain'] = $DomainRecordInfos[$i]['Domain'];
			$this->queryParameters['DomainRecordInfo.' . ($i + 1) . '.Type'] = $DomainRecordInfos[$i]['Type'];
			$this->queryParameters['DomainRecordInfo.' . ($i + 1) . '.Priority'] = $DomainRecordInfos[$i]['Priority'];
			$this->queryParameters['DomainRecordInfo.' . ($i + 1) . '.Value'] = $DomainRecordInfos[$i]['Value'];
			$this->queryParameters['DomainRecordInfo.' . ($i + 1) . '.Ttl'] = $DomainRecordInfos[$i]['Ttl'];

		}
	}

	public function getLang() {
		return $this->lang;
	}

	public function setLang($lang) {
		$this->lang = $lang;
		$this->queryParameters["Lang"]=$lang;
	}

	public function getType() {
		return $this->type;
	}

	public function setType($type) {
		$this->type = $type;
		$this->queryParameters["Type"]=$type;
	}
	
}