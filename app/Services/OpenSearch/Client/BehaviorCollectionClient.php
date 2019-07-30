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

namespace App\Services\OpenSearch\Client;

use App\Services\OpenSearch\Generated\BehaviorCollection\Command;
use App\Services\OpenSearch\Generated\BehaviorCollection\Constant;
use App\Services\OpenSearch\Generated\BehaviorCollection\BehaviorCollectionServiceIf;
use App\Services\OpenSearch\Client\OpenSearchClient;

/**
 * 搜索行为数据文档推送类。
 *
 * 管理搜索应用的行为数据推送，包含单条推送文档、批量推送文档等。
 *
 */
class BehaviorCollectionClient implements BehaviorCollectionServiceIf {

	private $openSearchClient;
	private $recordBuffer = array();

	const SEARCH_DOC_CLICK_EVENT_ID = 2001;

	/**
     * 构造方法。
     *
     * @param \App\Services\OpenSearch\Client\OpenSearchClient $openSearchClient 基础类，负责计算签名，和服务端进行交互和返回结果。
     * @return void
     */
	public function __construct($openSearchClient) {
        $this->openSearchClient = $openSearchClient;
    }

    /**
     * 增加一条搜索点击文档。
     *
     * > Note:
     * >
     * > 这条文档只是增加到sdk client buffer中，没有正式提交到服务端；只有调用了commit方法才会被提交到服务端。
     * > 你可以多次addSearchDocClickRecord然后调用commit() 统一提交。
     *
     * @param string $searchDocListPage 	搜索结果列表所在的页面名称
     * @param string $docDetailPage 		某个搜索文档被点击后，搜索文档的详情页面名称
     * @param int    $detailPageStayTime 	用户在详情页停留的时长(单位为ms)
     * @param string $objectId 				被点击的文档的主键，不能为空
     * @param string $opsRequestMisc 		opensearch返回的查询结果中的ops_request_misc字段
     * @param string $basicFields 			其他基础字段, 非必需字段
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function addSearchDocClickRecord(
    	$searchDocListPage,
    	$docDetailPage,
    	$detailPageStayTime,
		$objectId,
		$opsRequestMisc,
		array $basicFields = []) {

    	$jsonFields = [
    		'event_id'    => self::SEARCH_DOC_CLICK_EVENT_ID,
    		'sdk_type'    => OpenSearchClient::SDK_TYPE,
    		'sdk_version' => OpenSearchClient::SDK_VERSION,
    		'page' => $docDetailPage,
    		'arg1' => $searchDocListPage,
    		'arg2' => "",
    		'arg3' => $detailPageStayTime,
    		'args' => self::createSearchDocClickArgs($objectId, $opsRequestMisc),
    	];

    	if (!empty($basicFields)) {
    		foreach ($basicFields as $key => $value) {
    			$jsonFields[$key] = $value;
    		}
    	}

    	$this->addOneRecord($jsonFields, Command::$__names[Command::ADD]);
    }

    /**
     * 把sdk client buffer中的文档发布到服务端。
     *
     * > Note:
     * >
     * > 在发送之前会把buffer中的文档清空，所以如果服务端返回错误需要重试的情况下，需要重新生成文档并commit，避免丢数据的可能。
     *
     * @param string $searchAppName 			关联的搜索应用名
     * @param string $behaviorCollectionName	行为数据采集名称，开通时控制台会返回该名称
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function commit($searchAppName, $behaviorCollectionName) {
    	$recordsJson = json_encode($this->recordBuffer);
    	$this->recordBuffer = array();
    	return $this->doPush($recordsJson, $searchAppName, $behaviorCollectionName);
    }

    /**
     * 批量推送文档。
     *
     * > Note：
     * >
     * > 此操作会同步发送文档到服务端。
     *
     * @param string $recordsJson 				文档list的json
     * @param string $searchAppName 			关联的搜索应用名
     * @param string $behaviorCollectionName	行为数据采集名称，开通时控制台会返回该名称
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function push($recordsJson, $searchAppName, $behaviorCollectionName) {
    	return $this->doPush($recordsJson, $searchAppName, $behaviorCollectionName);
    }

    private function doPush($recordsJson, $searchAppName, $behaviorCollectionName) {
    	$path = self::createPushPath($searchAppName, $behaviorCollectionName);
    	return $this->openSearchClient->post($path, $recordsJson);
    }

    private function addOneRecord($jsonFields, $command) {
    	$cmdName    = Constant::get('DOC_KEY_CMD');
    	$fieldsName = Constant::get('DOC_KEY_FIELDS');

    	$jsonRecord = [
    		$cmdName    => $command,
    		$fieldsName => $jsonFields
    	];

    	$this->recordBuffer[] = $jsonRecord;
    }

    private static function createPushPath($searchAppName, $behaviorCollectionName) {
    	return sprintf("/app-groups/%s/data-collections/%s/actions/bulk", $searchAppName, $behaviorCollectionName);
    }

    private static function createSearchDocClickArgs($objectId, $opsRequestMisc) {
    	return sprintf("object_id=%s,object_type=ops_search_doc,ops_request_misc=%s", $objectId, $opsRequestMisc);
    }
}
