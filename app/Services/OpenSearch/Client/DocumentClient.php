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

use App\Services\OpenSearch\Generated\Document\Command;
use App\Services\OpenSearch\Generated\Document\Constant;
use App\Services\OpenSearch\Generated\Document\DocumentServiceIf;

/**
 * 应用文档操作类。
 *
 * 管理应用的文档，包含推送文档，删除文档，更新文档，批量推送文档等。
 *
 */
class DocumentClient implements DocumentServiceIf {

    private $openSearchClient;

    const DOCUMENT_API_PATH = '/apps';

    /**
     * @var sdk缓存的文档的数量。
     */
    public $docs = array();

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
     * 增加一条文档。
     *
     * > Note:
     * >
     * > 这条文档只是增加到sdk client buffer中，没有正式提交到服务端；只有调用了commit方法才会被提交到服务端。
     * 你可以add多次然后调用commit() 统一提交。
     *
     * @param array $fields 一条文档的所有字段，例如array("id" => 1, "name" => "tony");
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function add($fields) {
        $this->pushOneDoc($fields, Command::$__names[Command::ADD]);
    }

    /**
     * 修改一条文档。
     *
     * > Note:
     * >
     * > 这条文档只是增加到sdk client buffer中，没有正式提交到服务端；只有调用了commit方法才会被提交到服务端。
     * 你可以update多次然后调用commit() 统一提交。
     *
     * > 标准版不支持update操作。
     *
     * @param array $fields 一条文档的所有字段，例如array("id" => 1, "name" => "tony");
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function update($fields) {
        $this->pushOneDoc($fields, Command::$__names[Command::UPDATE]);
    }

    /**
     * 删除一条文档。
     *
     * > Note:
     * >
     * > 这条文档只是增加到sdk client buffer中，没有正式提交到服务端；只有调用了commit方法才会被提交到服务端。
     * 你可以remove多次然后调用commit() 统一提交。
     *
     * @param array $fields 一条文档的主键字段，例如array("id" => 1);
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function remove($fields) {
        $this->pushOneDoc($fields, Command::$__names[Command::DELETE]);
    }

    /**
     * 批量推送文档。
     *
     * > Note：
     * >
     * > 此操作会同步发送到服务端。
     *
     * @param string $docsJson 文档list的json，例如[{"cmd":"ADD","fields":{"id":"1","name":"tony"}},...]
     * @param string $appName 指定的app name或者app ID
     * @param string $tableName 指定的table name
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function push($docsJson, $appName, $tableName) {
        $path = self::_getPath($appName, $tableName);
        return $this->openSearchClient->post($path, $docsJson);
    }

    /**
     * 把client buffer中的文档发布到服务端。
     *
     * > Note:
     * >
     * > 在发送之前会把buffer中的文档清空，所以如果服务端返回错误需要重试的情况下，需要重新生成文档并commit，避免丢数据的可能。
     *
     * @param string $appName 指定的app name或者app ID
     * @param string $tableName 指定的table name
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function commit($appName, $tableName) {
        $json = json_encode($this->docs);
        $this->docs = array();
        return $this->push($json, $appName, $tableName);
    }

    /**
     * 推送一条文档到客户端buffer中。
     *
     * @param array $fields 一条文档的所有字段，例如array("id" => 1, "name" => "tony");
     * @param string $cmd 文档的操作类型，有ADD, UPDATE和DELETE;
     * @return \App\Services\OpenSearch\Generated\Common\OpenSearchResult
     */
    public function pushOneDoc($fields, $cmd) {
        $cmdName = Constant::get('DOC_KEY_CMD');
        $fieldName = Constant::get('DOC_KEY_FIELDS');
        $this->docs[] = array($cmdName => $cmd, $fieldName => $fields);
    }

    private static function _getPath($appName, $tableName) {
        return self::DOCUMENT_API_PATH . sprintf("/%s/%s/actions/bulk", $appName, $tableName);
    }
}
