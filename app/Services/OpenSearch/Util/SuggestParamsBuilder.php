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

namespace App\Services\OpenSearch\Util;

use App\Services\OpenSearch\Generated\Search\SearchParams;
use App\Services\OpenSearch\Generated\Search\Config;
use App\Services\OpenSearch\Generated\Search\Suggest;

class SuggestParamsBuilder
{

    public function __construct() {}

    /**
     * 创建一个下拉提示的搜索请求。
     *
     * @param string $appName 指定应用的名称。
     * @param string $suggestName 指定下拉提示的名称。
     * @param string $query 指定要搜索的关键词。
     * @param int $hits 指定要返回的词条个数。
     *
     * @return \App\Services\OpenSearch\Generated\Search\SearchParams
     */
    public static function build($appName, $suggestName, $query, $hits) {
        $config = new Config(array('hits' => (int) $hits, 'appNames' => array($appName)));
        $suggest = new Suggest(array('suggestName' => $suggestName));

        return new SearchParams(array("config" => $config, 'query' => $query, 'suggest' => $suggest));
    }

    /**
     * 根据SearchParams生成下拉提示搜索的参数。
     *
     * @param \App\Services\OpenSearch\Generated\Search\SearchParams $searchParams searchParams
     *
     * @return array
     */
    public static function getQueryParams($searchParams) {
        $query = $searchParams->query;
        $hits = $searchParams->config->hits;

        return array('query' => $query, 'hit' => $hits);
    }
}
