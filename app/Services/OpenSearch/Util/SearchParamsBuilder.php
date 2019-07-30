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

use App\Services\OpenSearch\Generated\Search\Aggregate;
use App\Services\OpenSearch\Generated\Search\Distinct;
use App\Services\OpenSearch\Generated\Search\Config;
use App\Services\OpenSearch\Generated\Search\SearchFormat;
use App\Services\OpenSearch\Generated\Search\SearchParams;
use App\Services\OpenSearch\Generated\Search\Sort;
use App\Services\OpenSearch\Generated\Search\SortField;
use App\Services\OpenSearch\Generated\Search\Summary;
use App\Services\OpenSearch\Generated\Search\DeepPaging;
use App\Services\OpenSearch\Generated\Search\Abtest;

/**
 * 搜索配置项。
 */
class SearchParamsBuilder
{
    const SORT_INCREASE = 1;
    const SORT_DECREASE = 0;

    private $searchParams;

    public function __construct($opts = array())
    {

        $config = new Config();
        $this->searchParams = new SearchParams(array('config' => $config));

        if (isset($opts['start'])) {
            $this->setStart($opts['start']);
        }

        if (isset($opts['hits'])) {
            $this->setHits($opts['hits']);
        }

        if (isset($opts['format'])) {
            $this->setFormat($opts['format']);
        }

        if (isset($opts['appName'])) {
            $this->setAppName($opts['appName']);
        }

        if (isset($opts['query'])) {
            $this->setQuery($opts['query']);
        }

        if (isset($opts['kvpairs'])) {
            $this->setKvPairs($opts['kvpairs']);
        }

        if (isset($opts['fetchFields'])) {
            $this->setFetchFields($opts['fetchFields']);
        }

        if (isset($opts['routeValue'])) {
            $this->setRouteValue($opts['routeValue']);
        }

        if (isset($opts['customConfig']) && is_array($opts['customConfig'])) {
            foreach ($opts['customConfig'] as $k => $v) {
                $this->setCustomConfig($k, $v);
            }
        }

        if (isset($opts['filter'])) {
            $this->setFilter($opts['filter']);
        }

        if (isset($opts['sort']) && is_array($opts['sort'])) {
            foreach ($opts['sort'] as $sort) {
                if (!isset($sort['order'])) {
                    $sort['order'] = SELF::SORT_DECREASE;
                }
                $this->addSort($sort['field'], $sort['order']);
            }
        }

        if (isset($opts['firstRankName'])) {
            $this->setFirstRankName($opts['firstRankName']);
        }

        if (isset($opts['secondRankName'])) {
            $this->setSecondRankName($opts['secondRankName']);
        }

        if (isset($opts['aggregate']) && isset($opts['aggregate']['groupKey'])) {
            $this->addAggregate($opts['aggregate']);
        } else if (isset($opts['aggregate']) && isset($opts['aggregate'][0])) {
            foreach ($opts['aggregate'] as $aggregate) {
                $this->addAggregate($aggregate);
            }
        }

        if (isset($opts['distinct']) && isset($opts['distinct'][0])) {
            foreach ($opts['distinct'] as $distinct) {
                $this->addDistinct($distinct);
            }
        } else if (isset($opts['distinct']) && isset($opts['distinct']['key'])) {
            $this->addDistinct($opts['distinct']);
        }

        if (isset($opts['summaries'])) {
            foreach ($opts['summaries'] as $summary) {
                $this->addSummary($summary);
            }
        }

        if (isset($opts['qp'])) {
            if (!is_array($opts['qp'])) {
                $opts['qp'] = array($opts['qp']);
            }
            foreach ($opts['qp'] as $qp) {
                $this->addQueryProcessor($qp);
            }
        }

        if (isset($opts['disableFunctions']) && is_array($opts['disableFunctions'])) {
            foreach ($opts['disableFunctions'] as $fun) {
                $this->addDisableFunctions($fun);
            }
        } else if (isset($opts['disableFunctions'])) {
            $this->addDisableFunctions($opts['disableFunctions']);
        }

        if (isset($opts['customParams'])) {
            foreach ($opts['customParams'] as $key => $value) {
                $this->setCustomParam($key, $value);
            }
        }

        if (isset($opts['reRankSize'])) {
            $this->setReRankSize($opts['reRankSize']);
        }
    }

    /**
     * 设置返回结果的偏移量。
     *
     * @param int $start 偏移量，范围[0,5000]。
     * @return void
     */
    public function setStart($start) {
        $this->searchParams->config->start = (int) $start;
    }

    /**
     * 设置返回结果的条数。
     *
     * @param int $hits 返回结果的条数，范围[0,500]。
     * @return void
     */
    public function setHits($hits) {
        $this->searchParams->config->hits = $hits;
    }

    /**
     * 设置返回结果的格式。
     *
     * @param String $format 返回结果的格式，有json、fulljson和xml格式。
     * @return void
     */
    public function setFormat($format) {
        $upperFormat = strtoupper($format);
        $this->searchParams->config->searchFormat = array_search($upperFormat, SearchFormat::$__names);
    }

    /**
     * 设置要搜索的应用名称或ID。
     *
     * @param String $appName 指定要搜索的应用名称或ID。
     * @return void
     */
    public function setAppName($appNames) {
        $this->searchParams->config->appNames = is_array($appNames) ? $appNames : array($appNames);
    }

    /**
     * 设置搜索关键词。
     *
     * @param String $query 设置的搜索关键词，格式为：索引名:'关键词' [AND|OR ...]
     * @return void
     */
    public function setQuery($query) {
        $this->searchParams->query = $query;
    }

    /**
     * 设置KVpairs。
     *
     * @param String $kvPairs 设置kvpairs。
     * @return void
     */
    public function setKvPairs($kvPairs) {
        $this->searchParams->config->kvpairs = $kvPairs;
    }

    /**
     * 设置结果集的返回字段。
     *
     * @param array $fetchFields 指定的返回字段的列表，例如array('a', 'b')
     * @return void
     */
    public function setFetchFields($fetchFields) {
        $this->searchParams->config->fetchFields = $fetchFields;
    }

    /**
     * 如果分组查询时，指定分组的值。
     *
     * @param Mixed $routeValue 分组字段值。
     * @return void
     */
    public function setRouteValue($routeValue) {
        $this->searchParams->config->routeValue = $routeValue;
    }

    /**
     * 设置参与精排个数。
     *
     * @param int $reRankSize 参与精排个数，范围[0,2000]。
     * @return void
     */
    public function setReRankSize($reRankSize) {
        $this->searchParams->rank->reRankSize = $reRankSize;
    }

    /**
     * 在Config字句中增加自定义的参数。
     *
     * @param String $key 设定自定义参数名。
     * @param Mixed $value 设定自定义参数值。
     * @return void
     */
    public function setCustomConfig($key, $value) {
        if ($this->searchParams->config->customConfig == null) {
            $this->searchParams->config->customConfig = array();
        }

        $this->searchParams->config->customConfig[$key] = $value;
    }

    /**
     * 添加过滤条件。
     *
     * @param String $filter 过滤，例如a>1。
     * @param String $condition 两个过滤条件的连接符, 例如AND OR等。
     * @return void
     */
    public function addFilter($filter, $condition = 'AND') {
        if ($this->searchParams->filter == null) {
            $this->searchParams->filter = $filter;
        } else {
            $this->searchParams->filter .= " {$condition} $filter";
        }
    }

    /**
     * 设置过滤条件。
     *
     * @param String $filterSting 过滤，例如a>1 OR b<2。
     * @return void
     */
    public function setFilter($filterString) {
        $this->searchParams->filter = $filterString;
    }

    /**
     * 添加排序规则。
     *
     * @param String $field 排序字段。
     * @param int $sort 排序策略，有降序0或者升序1，默认降序。
     * @return void
     */
    public function addSort($field, $order = self::SORT_DECREASE) {
        if ($this->searchParams->sort == null) {
            $this->searchParams->sort = new Sort();
            $this->searchParams->sort->sortFields = array();
        }
        $sortField = new SortField(array('field' => $field, 'order' => $order));
        $this->searchParams->sort->sortFields[] = $sortField;
    }

    /**
     * 设置粗排表达式名称。
     *
     * @param String $firstRankName 指定的粗排表达式名称。
     * @return void
     */
    public function setFirstRankName($firstRankName) {
        $this->searchParams->rank->firstRankName = $firstRankName;
    }

    /**
     * 设置精排表达式名称。
     *
     * @param String $secondRankName 指定的精排表达式名称。
     * @return void
     */
    public function setSecondRankName($secondRankName) {
        $this->searchParams->rank->secondRankName = $secondRankName;
    }

    /**
     * 设置聚合配置。
     *
     * @param array $agg 指定的聚合配置。
     * @return void
     */
    public function addAggregate($agg) {
        $aggregate = new Aggregate($agg);
        if ($this->searchParams->aggregates == null) {
            $this->searchParams->aggregates = array();
        }
        $this->searchParams->aggregates[] = $aggregate;
    }

    /**
     * 设置去重配置。
     *
     * @param array $dist 指定的去重配置。
     * @return void
     */
    public function addDistinct($dist) {
        $distinct = new Distinct($dist);
        if ($this->searchParams->distincts == null) {
            $this->searchParams->distincts = array();
        }
        $this->searchParams->distincts[] = $distinct;
    }

    /**
     * 设置搜索结果摘要配置。
     *
     * @param array $summaryMeta 指定的摘要字段配置。
     * @return void
     */
    public function addSummary($summaryMeta) {
        $summary = new Summary($summaryMeta);
        if ($this->searchParams->summaries == null) {
            $this->searchParams->summaries = array();
        }

        $this->searchParams->summaries[] = $summary;
    }

    /**
     * 添加查询分析配置。
     *
     * @param array $qpName 指定的QP名称。
     * @return void
     */
    public function addQueryProcessor($qpName) {
        if ($this->searchParams->queryProcessorNames == null) {
            $this->searchParams->queryProcessorNames = array();
        }

        $this->searchParams->queryProcessorNames[] = $qpName;
    }

    /**
     * 添加要关闭的function。
     *
     * @param String $disabledFunction 指定的要关闭的方法名称。
     * @return void
     */
    public function addDisableFunctions($disabledFunction) {
        if ($this->searchParams->disableFunctions == null) {
            $this->searchParams->disableFunctions = array();
        }

        $this->searchParams->disableFunctions[] = $disabledFunction;
    }

    /**
     * 设置自定义参数。
     *
     * @param String $key 自定义参数的参数名。
     * @param String $value 自定义参数的参数值。
     * @return void
     */
    public function setCustomParam($key, $value) {
        if ($this->searchParams->customParam == null) {
            $this->searchParams->customParam = array();
        }

        $this->searchParams->customParam[$key] = $value;
    }

    /**
     * 设置扫描数据的过期时间。
     *
     * @param String $expireTime 设定scroll的过期时间。
     * @return void
     */
    public function setScrollExpire($expiredTime) {
        if ($this->searchParams->deepPaging == null) {
            $this->searchParams->deepPaging = new DeepPaging();
        }

        $this->searchParams->deepPaging->scrollExpire = $expiredTime;
    }

    /**
     * 设置扫描数据的scrollId。
     *
     * ScrollId 为上一次扫描时返回的信息。
     *
     * @param String $scrollId 设定scroll的scrollId。
     * @return void
     */
    public function setScrollId($scrollId) {
        if ($this->searchParams->deepPaging == null) {
            $this->searchParams->deepPaging = new DeepPaging();
        }

        $this->searchParams->deepPaging->scrollId = $scrollId;
    }

    /**
     * 设置abtest数据的sceneTag。
     *
     * SceneTag 为场景标签。
     *
     * @param String $sceneTag 设定abtest的sceneTag。
     * @return void
     */
    public function setSceneTag($sceneTag) {
        if ($this->searchParams->abtest == null) {
            $this->searchParams->abtest = new Abtest();
        }

        $this->searchParams->abtest->sceneTag = $sceneTag;
    }

    /**
     * 设置abtest数据的flowDivider。
     *
     * FlowDivider 为流量分配标识。
     *
     * @param String $flowDivider 设定abtest的flowDivider。
     * @return void
     */
    public function setFlowDivider($flowDivider) {
        if ($this->searchParams->abtest == null) {
            $this->searchParams->abtest = new Abtest();
        }

        $this->searchParams->abtest->flowDivider = $flowDivider;
    }

    /**
     * 设置终端用户的id，用来统计uv信息。
     *
     * @param String $userId 设定终端用户的id。
     * @return void
     */
    public function setUserId($userId) {
        $this->searchParams->userId = $userId;
    }

    /**
     * 设置终端用户输入的query。
     *
     * @param String $rawQuery 设定终端用户输入的query。
     * @return void
     */
    public function setRawQuery($rawQuery) {
        $this->searchParams->rawQuery = $rawQuery;
    }

    /**
     * 获取SearchParams对象。
     *
     * @return SearchParams
     */
    public function build() {
        return $this->searchParams;
    }
}
