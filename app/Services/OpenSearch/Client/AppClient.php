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

/**
 * 应用基本信息管理类。
 *
 * 管理应用的基本信息，包含创建应用(save)、修改应用(updateById)、删除应用(removeById)
 * 、获取应用的基本详情(getById)、获取应用列表(listAll)、给应用导入全量数据(reindexById)
 * 等方法。
 *
 */
class AppClient implements AppServiceIf {

    private $openSearchClient = null;

    private $path = "/apps";

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
     * 创建一个新应用，或者创建一个新版本。
     *
     * 创建一个新的应用或者创建一个新的版本，如果在$app中指定了name，则会创建一个新版本，否则会创建一个新应用。
     *
     * > 创建版本的个数依赖服务端的限制。
     *
     * @param string $app 要创建的应用主体JSON，包含name、type、schema、quota、first_ranks、second_ranks、summary、data_sources、suggest、fetch_fields、query_processors等信息。
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult OpenSearchResult类
     */
    public function save($app) {
        return $this->openSearchClient->post($this->path, $app);
    }

    /**
     * 通过应用名称或者应用ID获取一个应用的详情信息。
     *
     * @param string $identity 要查询的应用名称或者应用ID，如果应用有多个版本，则指定应用名称为当前应用的在线版本。
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function getById($identity) {
        $path = $this->path . "/" . $identity;
        return $this->openSearchClient->get($path);
    }

    /**
     * 获取应用列表。
     *
     * @param \App\Services\OpenSearch\Generated\Common\Pageable $pageable 分页信息，包含页码和每页展示条数。
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function listAll(Pageable $pageable) {
        return $this->openSearchClient->get(
            $this->path, array('page' => $pageable->page, 'size' => $pageable->size)
        );
    }

    /**
     * 根据指定的应用id或名称删除应用版本或者应用；当指定的为应用名称，则表示指定的为当前应用分组中的在线的应用。。
     *
     * 如果当前应用只有一个版本，则会删除这个应用的整个分组；
     * 如果当前应用分组有多个应用，则需要当前要删除的版本不能处于在线状态。
     *
     * @param string $identity 指定的应用ID或者应用名称。
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function removeById($identity) {
        $path = $this->path . "/" . $identity;
        return $this->openSearchClient->delete($path);
    }

    /**
     * 更新某个应用的信息。
     *
     * @param string $identity 指定的应用ID或者应用名称；当指定的为应用名称，则表示指定的为当前应用分组中的在线的应用。
     * @param string $app 修改一个应用的应用结构json，包含name、type、schema、quota、first_ranks、second_ranks、summary、data_sources、suggest、fetch_fields、query_processors等信息。
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function updateById($identity, $app) {
        $path = $this->path . "/" . $identity;
        return $this->openSearchClient->patch($path, $app);
    }

    /**
     * 在创建过程中全量导入数据。
     *
     * @param string $identity 指定的应用ID或者应用名称；当指定的为应用名称，则表示指定的为当前应用分组中的在线的应用。。
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function reindexById($identity) {
        $path = $this->path . "/{$identity}/actions/reindex";
        return $this->openSearchClient->post($path);
    }
}
