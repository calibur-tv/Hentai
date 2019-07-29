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

use App\Services\OpenSearch\Generated\Search\Config;
use App\Services\OpenSearch\Generated\Search\OpenSearchSearcherServiceIf;
use App\Services\OpenSearch\Generated\Search\SearchFormat;
use App\Services\OpenSearch\Generated\Search\SearchParams;
use App\Services\OpenSearch\Util\UrlParamsBuilder;

class SearchClient implements OpenSearchSearcherServiceIf {

    const SEARCH_API_PATH = '/apps/%s/search';

    private $openSearchClient;

    public function __construct($openSearchClient) {
        $this->openSearchClient = $openSearchClient;
    }

    public function execute(SearchParams $searchParams) {
        $path = self::getPath($searchParams);
        $builder = new UrlParamsBuilder($searchParams);
        return $this->openSearchClient->get($path, $builder->getHttpParams());
    }

    private static function getPath($searchParams) {
        $appNames = isset($searchParams->config->appNames) ? implode(',', $searchParams->config->appNames) : '';
        return sprintf(self::SEARCH_API_PATH, $appNames);
    }
}