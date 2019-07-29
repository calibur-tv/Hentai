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

use App\Services\OpenSearch\Generated\App\AppServiceIf;
use App\Services\OpenSearch\Generated\Common\Pageable;

class AppClient implements AppServiceIf {
    private $openSearchClient;
    private $path = "/apps";

    public function __construct($openSearchClient) {
        $this->openSearchClient = $openSearchClient;
    }

    /**
     * 创建一个新应用，或者创建一个新版本。
     *
     * @param String $app 要创建的应用主体JSON，包含name,schema,quota等信息。
     * @return OpenSearch\Generated\Common\OpenSearchResult
     */
    public function save($app) {
        return $this->openSearchClient->post($this->path, $app);
    }

    /**
     * 通过应用名称或者应用ID获取一个应用的详情信息。
     *
     * @param String $identity 要查询的应用名称或者应用ID，如果应用有多个版本，则指定应用名称为当前应用的在线版本。
     * @return OpenSearch\Generated\Common\OpenSearchResult
     */
    public function getById($identity) {
        $path = $this->path . "/" . $identity;
        return $this->openSearchClient->get($path);
    }

    /**
     * 获取应用列表。
     *
     * @param OpenSearch\Generated\Common\Pageable $pageable 分页信息，包含页码和每页展示条数。
     * @return OpenSearch\Generated\Common\OpenSearchResult
     */
    public function listAll(Pageable $pageable) {
        return $this->openSearchClient->get(
            $this->path, array('page' => $pageable->page, 'size' => $pageable->size)
        );
    }

    /**
     * 根据指定的应用id或名称删除应用版本或者应用。
     *
     * 如果当前应用只有一个版本，则会删除这个应用；
     * 如果当前应用有多个版本，则可以删除不在线的版本。
     *
     * @param String $identity 指定的应用ID或者应用名称。
     * @return OpenSearch\Generated\Common\OpenSearchResult
     */
    public function removeById($identity) {
        $path = $this->path . "/" . $identity;
        return $this->openSearchClient->delete($path);
    }

    /**
     * 更新某个应用的信息。
     *
     * @param String $identity 指定的应用ID或者应用名称。
     * @return OpenSearch\Generated\Common\OpenSearchResult
     */
    public function updateById($identity, $app) {
        $path = $this->path . "/" . $identity;
        return $this->openSearchClient->patch($path, $app);
    }

    /**
     * 在创建过程中全量导入数据。
     *
     * @param String $identity 指定的应用ID或者应用名称。
     * @return OpenSearch\Generated\Common\OpenSearchResult
     */
    public function reindexById($identity) {
        $path = $this->path . "/{$identity}/actions/reindex";
        return $this->openSearchClient->post($path);
    }
}