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

use App\Services\OpenSearch\Generated\Search\OpenSearchSearcherServiceIf;
use App\Services\OpenSearch\Generated\Search\SearchParams;
use App\Services\OpenSearch\Util\SuggestParamsBuilder;

/**
 * 应用下拉提示操作类。
 *
 * 通过制定关键词、过滤条件搜索应用的下拉提示的结果。
 *
 */
class SuggestClient implements OpenSearchSearcherServiceIf {

    const SUGGEST_API_PATH = '/apps/%s/suggest/%s/search';

    private $openSearchClient;

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
     * 执行搜索操作。
     *
     * @param \App\Services\OpenSearch\Generated\Search\SearchParams $searchParams 制定的搜索条件。
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult OpenSearchResult类
     */
    public function execute(SearchParams $searchParams) {
        $path = self::getPath($searchParams);
        $params = SuggestParamsBuilder::getQueryParams($searchParams);
        return $this->openSearchClient->get($path, $params);
    }

    private static function getPath($searchParams) {
        $appName = implode(',', $searchParams->config->appNames);
        $suggestName = $searchParams->suggest->suggestName;

        return sprintf(self::SUGGEST_API_PATH, $appName, $suggestName);
    }
}
