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

use App\Services\OpenSearch\Generated\Search\Constant;
use App\Services\OpenSearch\Generated\Search\DeepPaging;

class UrlParamsBuilder
{
    const QUERY = 'query';
    const FORMAT = 'format';
    const FIRST_RANK_NAME = 'first_rank_name';
    const SECOND_RANK_NAME = 'second_rank_name';
    const SUMMARY = 'summary';
    const FETCH_FIELDS = 'fetch_fields';
    const QP = 'qp';
    const DISABLE = 'disable';
    const ROUTE_VALUE = 'route_value';
    const SCROLL_EXPIRE = 'scroll';
    const SCROLL_ID = 'scroll_id';
    const SEARCH_TYPE = 'search_type';

    const FETCH_FIELDS_SEPARATOR = ';';
    const QP_SEPARATOR = ',';
    const DISABLE_FUNCTIONS_SEPARATOR = ';';

    const SUMMARY_SEPARATOR = ';';
    const SUMMARY_SUB_SEPARATOR = ',';
    const SUMMARY_KV_SEPARATOR = ':';

    const SEARCH_TYPE_SCAN = 'scan';

    const ABTEST = "abtest";

    private static $summaryKeys = array(
        'summary_field' => 'SUMMARY_PARAM_SUMMARY_FIELD',
        'summary_len' => 'SUMMARY_PARAM_SUMMARY_LEN',
        'summary_ellipsis' => 'SUMMARY_PARAM_SUMMARY_ELLIPSIS',
        'summary_snippet' => 'SUMMARY_PARAM_SUMMARY_SNIPPET',
        'summary_element' => 'SUMMARY_PARAM_SUMMARY_ELEMENT',
        'summary_element_prefix' => 'SUMMARY_PARAM_SUMMARY_ELEMENT_PREFIX',
        'summary_element_postfix' => 'SUMMARY_PARAM_SUMMARY_ELEMENT_POSTFIX'
    );

    private $params = array();

    public function __construct($searchParams) {
        $this->init($searchParams);
    }

    public function init($searchParams) {
        $this->initQuery($searchParams);
        $this->initScroll($searchParams);
        $this->initRank($searchParams);
        $this->initFetchFields($searchParams);
        $this->initSummary($searchParams);
        $this->initQueryProcessor($searchParams);
        $this->initDisableFunctions($searchParams);
        $this->initRouteValue($searchParams);
        $this->initCustomParams($searchParams);
        $this->initAbtest($searchParams);
        $this->initUserId($searchParams);
        $this->initRawQuery($searchParams);
    }

    public function initScroll($searchParams) {
        if (isset($searchParams->deepPaging) && $searchParams->deepPaging instanceof DeepPaging) {
            if ($searchParams->deepPaging->scrollId) {
                $this->params[self::SCROLL_ID] = $searchParams->deepPaging->scrollId;
            } else {
                $this->params[self::SEARCH_TYPE] = self::SEARCH_TYPE_SCAN;
            }
            $this->params[self::SCROLL_EXPIRE] = $searchParams->deepPaging->scrollExpire;
        }
    }

    public function initQuery($searchParams) {
        $builder = new ClauseParamsBuilder($searchParams);
        $this->params[self::QUERY] = $builder->getClausesString();
    }

    public function initRank($searchParams) {
        if (isset($searchParams->rank->firstRankName)) {
            $this->params[self::FIRST_RANK_NAME] = $searchParams->rank->firstRankName;
        }

        if (isset($searchParams->rank->secondRankName)) {
            $this->params[self::SECOND_RANK_NAME] = $searchParams->rank->secondRankName;
        }
    }

    public function initFetchFields($searchParams) {
        if (isset($searchParams->config->fetchFields)) {
            $this->params[self::FETCH_FIELDS] = implode(self::FETCH_FIELDS_SEPARATOR, $searchParams->config->fetchFields);
        }
    }

    public function initSummary($searchParams) {
        if (isset($searchParams->summaries)) {
            $summaries = array();
            foreach ($searchParams->summaries as $summary) {
                if (!isset($summary->summary_field)) {
                    continue;
                }

                $sum = array();
                foreach (self::$summaryKeys as $k => $v) {
                    if (isset($summary->$k)) {
                        $sum[] = Constant::get($v) . self::SUMMARY_KV_SEPARATOR . $summary->$k;
                    }
                }

                $summaries[] = implode(self::SUMMARY_SUB_SEPARATOR, $sum);
            }
            $this->params[self::SUMMARY] = implode(self::SUMMARY_SEPARATOR, $summaries);
        }
    }

    public function initQueryProcessor($searchParams) {
        if (isset($searchParams->queryProcessorNames)) {
            $this->params[self::QP] = implode(self::QP_SEPARATOR, $searchParams->queryProcessorNames);
        }
    }

    public function initDisableFunctions($searchParams) {
        if (isset($searchParams->disableFunctions)) {
            $this->params[self::DISABLE] = implode(self::DISABLE_FUNCTIONS_SEPARATOR, $searchParams->disableFunctions);
        }
    }

    public function initRouteValue($searchParams) {
        if (isset($searchParams->config->routeValue)) {
            $this->params[self::ROUTE_VALUE] = $searchParams->config->routeValue;
        }
    }

    public function initCustomParams($searchParams) {
        if (isset($searchParams->customParam)) {
            $this->params = array_merge($this->params, $searchParams->customParam);
        }
    }

    public function initAbtest($searchParams) {
        if (isset($searchParams->abtest) && isset($searchParams->abtest->sceneTag) && isset($searchParams->abtest->flowDivider)) {
            $this->params[self::ABTEST] = sprintf("%s=%s,%s=%s", Constant::get('ABTEST_PARAM_SCENE_TAG'), $searchParams->abtest->sceneTag, Constant::get('ABTEST_PARAM_FLOW_DIVIDER'), $searchParams->abtest->flowDivider);
        }
    }

    public function initUserId($searchParams) {
        if (isset($searchParams->userId)) {
            $this->params[Constant::get('USER_ID')] = $searchParams->userId;
        }
    }

    public function initRawQuery($searchParams) {
        if (isset($searchParams->rawQuery)) {
            $this->params[Constant::get('RAW_QUERY')] = $searchParams->rawQuery;
        }
    }

    public function getHttpParams() {
        return $this->params;
    }
}
