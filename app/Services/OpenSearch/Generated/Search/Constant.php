<?php


namespace App\Services\OpenSearch\Generated\Search;


class Constant extends \App\Services\OpenSearch\Thrift\Type\TConstant {
    static protected $CONFIG_CLAUSE_START;
    static protected $CONFIG_CLAUSE_HIT;
    static protected $CONFIG_CLAUSE_RERANK_SIZE;
    static protected $CONFIG_CLAUSE_FORMAT;
    static protected $SORT_CLAUSE_INCREASE;
    static protected $SORT_CLAUSE_DECREASE;
    static protected $SORT_CLAUSE_RANK;
    static protected $DISTINCT_CLAUSE_DIST_KEY;
    static protected $DISTINCT_CLAUSE_DIST_COUNT;
    static protected $DISTINCT_CLAUSE_DIST_TIMES;
    static protected $DISTINCT_CLAUSE_RESERVED;
    static protected $DISTINCT_CLAUSE_DIST_FILTER;
    static protected $DISTINCT_CLAUSE_UPDATE_TOTAL_HIT;
    static protected $DISTINCT_CLAUSE_GRADE;
    static protected $AGGREGATE_CLAUSE_GROUP_KEY;
    static protected $AGGREGATE_CLAUSE_AGG_FUN;
    static protected $AGGREGATE_CLAUSE_RANGE;
    static protected $AGGREGATE_CLAUSE_MAX_GROUP;
    static protected $AGGREGATE_CLAUSE_AGG_FILTER;
    static protected $AGGREGATE_CLAUSE_AGG_SAMPLER_THRESHOLD;
    static protected $AGGREGATE_CLAUSE_AGG_SAMPLER_STEP;
    static protected $SUMMARY_PARAM_SUMMARY_FIELD;
    static protected $SUMMARY_PARAM_SUMMARY_LEN;
    static protected $SUMMARY_PARAM_SUMMARY_ELLIPSIS;
    static protected $SUMMARY_PARAM_SUMMARY_SNIPPET;
    static protected $SUMMARY_PARAM_SUMMARY_ELEMENT;
    static protected $SUMMARY_PARAM_SUMMARY_ELEMENT_PREFIX;
    static protected $SUMMARY_PARAM_SUMMARY_ELEMENT_POSTFIX;
    static protected $FORMAT_PARAM;
    static protected $ABTEST_PARAM_SCENE_TAG;
    static protected $ABTEST_PARAM_FLOW_DIVIDER;
    static protected $USER_ID;
    static protected $RAW_QUERY;

    static protected function init_CONFIG_CLAUSE_START() {
        return "start";
    }

    static protected function init_CONFIG_CLAUSE_HIT() {
        return "hit";
    }

    static protected function init_CONFIG_CLAUSE_RERANK_SIZE() {
        return "rerank_size";
    }

    static protected function init_CONFIG_CLAUSE_FORMAT() {
        return "format";
    }

    static protected function init_SORT_CLAUSE_INCREASE() {
        return "+";
    }

    static protected function init_SORT_CLAUSE_DECREASE() {
        return "-";
    }

    static protected function init_SORT_CLAUSE_RANK() {
        return "RANK";
    }

    static protected function init_DISTINCT_CLAUSE_DIST_KEY() {
        return "dist_key";
    }

    static protected function init_DISTINCT_CLAUSE_DIST_COUNT() {
        return "dist_count";
    }

    static protected function init_DISTINCT_CLAUSE_DIST_TIMES() {
        return "dist_times";
    }

    static protected function init_DISTINCT_CLAUSE_RESERVED() {
        return "reserved";
    }

    static protected function init_DISTINCT_CLAUSE_DIST_FILTER() {
        return "dist_filter";
    }

    static protected function init_DISTINCT_CLAUSE_UPDATE_TOTAL_HIT() {
        return "update_total_hit";
    }

    static protected function init_DISTINCT_CLAUSE_GRADE() {
        return "grade";
    }

    static protected function init_AGGREGATE_CLAUSE_GROUP_KEY() {
        return "group_key";
    }

    static protected function init_AGGREGATE_CLAUSE_AGG_FUN() {
        return "agg_fun";
    }

    static protected function init_AGGREGATE_CLAUSE_RANGE() {
        return "range";
    }

    static protected function init_AGGREGATE_CLAUSE_MAX_GROUP() {
        return "max_group";
    }

    static protected function init_AGGREGATE_CLAUSE_AGG_FILTER() {
        return "agg_filter";
    }

    static protected function init_AGGREGATE_CLAUSE_AGG_SAMPLER_THRESHOLD() {
        return "agg_sampler_threshold";
    }

    static protected function init_AGGREGATE_CLAUSE_AGG_SAMPLER_STEP() {
        return "agg_sampler_step";
    }

    static protected function init_SUMMARY_PARAM_SUMMARY_FIELD() {
        return "summary_field";
    }

    static protected function init_SUMMARY_PARAM_SUMMARY_LEN() {
        return "summary_len";
    }

    static protected function init_SUMMARY_PARAM_SUMMARY_ELLIPSIS() {
        return "summary_ellipsis";
    }

    static protected function init_SUMMARY_PARAM_SUMMARY_SNIPPET() {
        return "summary_snippet";
    }

    static protected function init_SUMMARY_PARAM_SUMMARY_ELEMENT() {
        return "summary_element";
    }

    static protected function init_SUMMARY_PARAM_SUMMARY_ELEMENT_PREFIX() {
        return "summary_element_prefix";
    }

    static protected function init_SUMMARY_PARAM_SUMMARY_ELEMENT_POSTFIX() {
        return "summary_element_postfix";
    }

    static protected function init_FORMAT_PARAM() {
        return "format";
    }

    static protected function init_ABTEST_PARAM_SCENE_TAG() {
        return "scene_tag";
    }

    static protected function init_ABTEST_PARAM_FLOW_DIVIDER() {
        return "flow_divider";
    }

    static protected function init_USER_ID() {
        return "user_id";
    }

    static protected function init_RAW_QUERY() {
        return "raw_query";
    }
}
