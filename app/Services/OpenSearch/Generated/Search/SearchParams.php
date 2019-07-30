<?php


namespace App\Services\OpenSearch\Generated\Search;


use App\Services\OpenSearch\Thrift\Exception\TProtocolException;
use App\Services\OpenSearch\Thrift\Type\TType;

class SearchParams
{
    static $_TSPEC;

    /**
     * config for search.
     *
     * @var \App\Services\OpenSearch\Generated\Search\Config
     */
    public $config = null;
    /**
     * 设定指定索引字段范围的搜索关键词(query)
     *
     * 此query是查询必需的一部分，可以指定不同的索引名，并同时可指定多个查询及之间的关系
     * （AND, OR, ANDNOT, RANK）。
     *
     * 例如查询subject索引字段的query:“手机”，可以设置为 query=subject:'手机'。
     *
     * 上边例子如果查询price 在1000-2000之间的手机，其查询语句为： query=subject:'手机'
     * AND price:[1000,2000]
     *
     * NOTE: text类型索引在建立时做了分词，而string类型的索引则没有分词。
     *
     * @link http://docs.aliyun.com/?spm=5176.2020520121.103.8.VQIcGd&tag=tun#/pub/opensearch/api-reference/query-clause&query-clause
     *
     *
     * @var string
     */
    public $query = null;
    /**
     * 过滤规则(filter)
     *
     * @var string
     */
    public $filter = null;
    /**
     * 排序字段及排序方式(sort)
     *
     * @var \App\Services\OpenSearch\Generated\Search\Sort
     */
    public $sort = null;
    /**
     * @var \App\Services\OpenSearch\Generated\Search\Rank
     */
    public $rank = null;
    /**
     * 添加统计信息(aggregate)相关参数
     *
     * @var \App\Services\OpenSearch\Generated\Search\Aggregate[]
     */
    public $aggregates = null;
    /**
     * 聚合打散条件
     *
     * @var \App\Services\OpenSearch\Generated\Search\Distinct[]
     */
    public $distincts = null;
    /**
     * 动态摘要(summary)信息
     *
     * @var \App\Services\OpenSearch\Generated\Search\Summary[]
     */
    public $summaries = null;
    /**
     * 设置查询分析规则(qp)
     *
     * @var string[]
     */
    public $queryProcessorNames = null;
    /**
     * @var \App\Services\OpenSearch\Generated\Search\DeepPaging
     */
    public $deepPaging = null;
    /**
     * 关闭某些功能模块(disable)
     *
     * 有如下场景需要考虑：
     * 1、如果要关闭整个qp的功能，则指定disableValue="qp"。
     * 2、要指定某个索引关闭某个功能，则可以指定disableValue="qp:function_name:index_names",
     *   其中index_names可以用“|”分隔，可以为index_name1|index_name2...
     * 3、如果要关闭多个function可以用“,”分隔，例如：disableValue="qp:function_name1:index_name1,qp:function_name2:index_name1"
     *
     * qp有如下模块：
     * 1、spell_check: 检查用户查询串中的拼写错误，并给出纠错建议。
     * 2、term_weighting: 分析查询中每个词的重要程度，并将其量化成权重，权重较低的词可能不会参与召回。
     * 3、stop_word: 根据系统内置的停用词典过滤查询中无意义的词
     * 4、synonym: 根据系统提供的通用同义词库和语义模型，对查询串进行同义词扩展，以便扩大召回。
     *
     * example:
     * "qp" 标示关闭整个qp
     * "qp:spell_check" 标示关闭qp的拼音纠错功能。
     * "qp:stop_word:index_name1|index_name2" 标示关闭qp中index_name1和index_name2上的停用词功能。
     *
     * key 需要禁用的函数名称
     * value 待禁用函数的详细说明
     *
     * @var array
     */
    public $disableFunctions = null;
    /**
     * @var array
     */
    public $customParam = null;
    /**
     * 下拉提示是搜索服务的基础功能，在用户输入查询词的过程中，智能推荐候选query，减少用户输入，帮助用户尽快找到想要的内容。
     * OpenSearch下拉提示在实现了中文前缀，拼音全拼，拼音首字母简拼查询等通用功能的基础上，实现了基于用户文档内容的query智能识别。
     * 用户通过控制台的简单配置，就能拥有专属的定制下拉提示。此外，控制台上还提供了黑名单，推荐词条功能，让用户进一步控制下拉提示
     * 的结果，实现更灵活的定制。
     *
     *
     * @var \App\Services\OpenSearch\Generated\Search\Suggest
     */
    public $suggest = null;
    /**
     * Abtest
     *
     * @var \App\Services\OpenSearch\Generated\Search\Abtest
     */
    public $abtest = null;
    /**
     * 终端用户的id，用来统计uv信息
     *
     * @var string
     */
    public $userId = null;
    /**
     * 终端用户输入的query
     *
     * @var string
     */
    public $rawQuery = null;

    public function __construct($vals=null) {
        if (!isset(self::$_TSPEC)) {
            self::$_TSPEC = array(
                3 => array(
                    'var' => 'config',
                    'type' => TType::STRUCT,
                    'class' => '\OpenSearch\Generated\Search\Config',
                ),
                5 => array(
                    'var' => 'query',
                    'type' => TType::STRING,
                ),
                7 => array(
                    'var' => 'filter',
                    'type' => TType::STRING,
                ),
                9 => array(
                    'var' => 'sort',
                    'type' => TType::STRUCT,
                    'class' => '\OpenSearch\Generated\Search\Sort',
                ),
                11 => array(
                    'var' => 'rank',
                    'type' => TType::STRUCT,
                    'class' => '\OpenSearch\Generated\Search\Rank',
                ),
                13 => array(
                    'var' => 'aggregates',
                    'type' => TType::SET,
                    'etype' => TType::STRUCT,
                    'elem' => array(
                        'type' => TType::STRUCT,
                        'class' => '\OpenSearch\Generated\Search\Aggregate',
                    ),
                ),
                15 => array(
                    'var' => 'distincts',
                    'type' => TType::SET,
                    'etype' => TType::STRUCT,
                    'elem' => array(
                        'type' => TType::STRUCT,
                        'class' => '\OpenSearch\Generated\Search\Distinct',
                    ),
                ),
                17 => array(
                    'var' => 'summaries',
                    'type' => TType::SET,
                    'etype' => TType::STRUCT,
                    'elem' => array(
                        'type' => TType::STRUCT,
                        'class' => '\OpenSearch\Generated\Search\Summary',
                    ),
                ),
                19 => array(
                    'var' => 'queryProcessorNames',
                    'type' => TType::LST,
                    'etype' => TType::STRING,
                    'elem' => array(
                        'type' => TType::STRING,
                    ),
                ),
                21 => array(
                    'var' => 'deepPaging',
                    'type' => TType::STRUCT,
                    'class' => '\OpenSearch\Generated\Search\DeepPaging',
                ),
                23 => array(
                    'var' => 'disableFunctions',
                    'type' => TType::MAP,
                    'ktype' => TType::STRING,
                    'vtype' => TType::STRING,
                    'key' => array(
                        'type' => TType::STRING,
                    ),
                    'val' => array(
                        'type' => TType::STRING,
                    ),
                ),
                27 => array(
                    'var' => 'customParam',
                    'type' => TType::MAP,
                    'ktype' => TType::STRING,
                    'vtype' => TType::STRING,
                    'key' => array(
                        'type' => TType::STRING,
                    ),
                    'val' => array(
                        'type' => TType::STRING,
                    ),
                ),
                29 => array(
                    'var' => 'suggest',
                    'type' => TType::STRUCT,
                    'class' => '\OpenSearch\Generated\Search\Suggest',
                ),
                30 => array(
                    'var' => 'abtest',
                    'type' => TType::STRUCT,
                    'class' => '\OpenSearch\Generated\Search\Abtest',
                ),
                31 => array(
                    'var' => 'userId',
                    'type' => TType::STRING,
                ),
                32 => array(
                    'var' => 'rawQuery',
                    'type' => TType::STRING,
                ),
            );
        }
        $this->rank = new \App\Services\OpenSearch\Generated\Search\Rank(array(
            "reRankSize" => 200,
        ));
        if (is_array($vals)) {
            if (isset($vals['config'])) {
                $this->config = $vals['config'];
            }
            if (isset($vals['query'])) {
                $this->query = $vals['query'];
            }
            if (isset($vals['filter'])) {
                $this->filter = $vals['filter'];
            }
            if (isset($vals['sort'])) {
                $this->sort = $vals['sort'];
            }
            if (isset($vals['rank'])) {
                $this->rank = $vals['rank'];
            }
            if (isset($vals['aggregates'])) {
                $this->aggregates = $vals['aggregates'];
            }
            if (isset($vals['distincts'])) {
                $this->distincts = $vals['distincts'];
            }
            if (isset($vals['summaries'])) {
                $this->summaries = $vals['summaries'];
            }
            if (isset($vals['queryProcessorNames'])) {
                $this->queryProcessorNames = $vals['queryProcessorNames'];
            }
            if (isset($vals['deepPaging'])) {
                $this->deepPaging = $vals['deepPaging'];
            }
            if (isset($vals['disableFunctions'])) {
                $this->disableFunctions = $vals['disableFunctions'];
            }
            if (isset($vals['customParam'])) {
                $this->customParam = $vals['customParam'];
            }
            if (isset($vals['suggest'])) {
                $this->suggest = $vals['suggest'];
            }
            if (isset($vals['abtest'])) {
                $this->abtest = $vals['abtest'];
            }
            if (isset($vals['userId'])) {
                $this->userId = $vals['userId'];
            }
            if (isset($vals['rawQuery'])) {
                $this->rawQuery = $vals['rawQuery'];
            }
        }
    }

    public function getName() {
        return 'SearchParams';
    }

    public function read($input)
    {
        $xfer = 0;
        $fname = null;
        $ftype = 0;
        $fid = 0;
        $xfer += $input->readStructBegin($fname);
        while (true)
        {
            $xfer += $input->readFieldBegin($fname, $ftype, $fid);
            if ($ftype == TType::STOP) {
                break;
            }
            switch ($fid)
            {
                case 3:
                    if ($ftype == TType::STRUCT) {
                        $this->config = new \App\Services\OpenSearch\Generated\Search\Config();
                        $xfer += $this->config->read($input);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 5:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->query);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 7:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->filter);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 9:
                    if ($ftype == TType::STRUCT) {
                        $this->sort = new \App\Services\OpenSearch\Generated\Search\Sort();
                        $xfer += $this->sort->read($input);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 11:
                    if ($ftype == TType::STRUCT) {
                        $this->rank = new \App\Services\OpenSearch\Generated\Search\Rank();
                        $xfer += $this->rank->read($input);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 13:
                    if ($ftype == TType::SET) {
                        $this->aggregates = array();
                        $_size28 = 0;
                        $_etype31 = 0;
                        $xfer += $input->readSetBegin($_etype31, $_size28);
                        for ($_i32 = 0; $_i32 < $_size28; ++$_i32)
                        {
                            $elem33 = null;
                            $elem33 = new \App\Services\OpenSearch\Generated\Search\Aggregate();
                            $xfer += $elem33->read($input);
                            if (is_scalar($elem33)) {
                                $this->aggregates[$elem33] = true;
                            } else {
                                $this->aggregates []= $elem33;
                            }
                        }
                        $xfer += $input->readSetEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 15:
                    if ($ftype == TType::SET) {
                        $this->distincts = array();
                        $_size34 = 0;
                        $_etype37 = 0;
                        $xfer += $input->readSetBegin($_etype37, $_size34);
                        for ($_i38 = 0; $_i38 < $_size34; ++$_i38)
                        {
                            $elem39 = null;
                            $elem39 = new \App\Services\OpenSearch\Generated\Search\Distinct();
                            $xfer += $elem39->read($input);
                            if (is_scalar($elem39)) {
                                $this->distincts[$elem39] = true;
                            } else {
                                $this->distincts []= $elem39;
                            }
                        }
                        $xfer += $input->readSetEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 17:
                    if ($ftype == TType::SET) {
                        $this->summaries = array();
                        $_size40 = 0;
                        $_etype43 = 0;
                        $xfer += $input->readSetBegin($_etype43, $_size40);
                        for ($_i44 = 0; $_i44 < $_size40; ++$_i44)
                        {
                            $elem45 = null;
                            $elem45 = new \App\Services\OpenSearch\Generated\Search\Summary();
                            $xfer += $elem45->read($input);
                            if (is_scalar($elem45)) {
                                $this->summaries[$elem45] = true;
                            } else {
                                $this->summaries []= $elem45;
                            }
                        }
                        $xfer += $input->readSetEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 19:
                    if ($ftype == TType::LST) {
                        $this->queryProcessorNames = array();
                        $_size46 = 0;
                        $_etype49 = 0;
                        $xfer += $input->readListBegin($_etype49, $_size46);
                        for ($_i50 = 0; $_i50 < $_size46; ++$_i50)
                        {
                            $elem51 = null;
                            $xfer += $input->readString($elem51);
                            $this->queryProcessorNames []= $elem51;
                        }
                        $xfer += $input->readListEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 21:
                    if ($ftype == TType::STRUCT) {
                        $this->deepPaging = new \App\Services\OpenSearch\Generated\Search\DeepPaging();
                        $xfer += $this->deepPaging->read($input);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 23:
                    if ($ftype == TType::MAP) {
                        $this->disableFunctions = array();
                        $_size52 = 0;
                        $_ktype53 = 0;
                        $_vtype54 = 0;
                        $xfer += $input->readMapBegin($_ktype53, $_vtype54, $_size52);
                        for ($_i56 = 0; $_i56 < $_size52; ++$_i56)
                        {
                            $key57 = '';
                            $val58 = '';
                            $xfer += $input->readString($key57);
                            $xfer += $input->readString($val58);
                            $this->disableFunctions[$key57] = $val58;
                        }
                        $xfer += $input->readMapEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 27:
                    if ($ftype == TType::MAP) {
                        $this->customParam = array();
                        $_size59 = 0;
                        $_ktype60 = 0;
                        $_vtype61 = 0;
                        $xfer += $input->readMapBegin($_ktype60, $_vtype61, $_size59);
                        for ($_i63 = 0; $_i63 < $_size59; ++$_i63)
                        {
                            $key64 = '';
                            $val65 = '';
                            $xfer += $input->readString($key64);
                            $xfer += $input->readString($val65);
                            $this->customParam[$key64] = $val65;
                        }
                        $xfer += $input->readMapEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 29:
                    if ($ftype == TType::STRUCT) {
                        $this->suggest = new \App\Services\OpenSearch\Generated\Search\Suggest();
                        $xfer += $this->suggest->read($input);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 30:
                    if ($ftype == TType::STRUCT) {
                        $this->abtest = new \App\Services\OpenSearch\Generated\Search\Abtest();
                        $xfer += $this->abtest->read($input);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 31:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->userId);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 32:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->rawQuery);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                default:
                    $xfer += $input->skip($ftype);
                    break;
            }
            $xfer += $input->readFieldEnd();
        }
        $xfer += $input->readStructEnd();
        return $xfer;
    }

    public function write($output) {
        $xfer = 0;
        $xfer += $output->writeStructBegin('SearchParams');
        if ($this->config !== null) {
            if (!is_object($this->config)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('config', TType::STRUCT, 3);
            $xfer += $this->config->write($output);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->query !== null) {
            $xfer += $output->writeFieldBegin('query', TType::STRING, 5);
            $xfer += $output->writeString($this->query);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->filter !== null) {
            $xfer += $output->writeFieldBegin('filter', TType::STRING, 7);
            $xfer += $output->writeString($this->filter);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->sort !== null) {
            if (!is_object($this->sort)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('sort', TType::STRUCT, 9);
            $xfer += $this->sort->write($output);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->rank !== null) {
            if (!is_object($this->rank)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('rank', TType::STRUCT, 11);
            $xfer += $this->rank->write($output);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->aggregates !== null) {
            if (!is_array($this->aggregates)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('aggregates', TType::SET, 13);
            {
                $output->writeSetBegin(TType::STRUCT, count($this->aggregates));
                {
                    foreach ($this->aggregates as $iter66 => $iter67)
                    {
                        if (is_scalar($iter67)) {
                            $xfer += $iter66->write($output);
                        } else {
                            $xfer += $iter67->write($output);
                        }
                    }
                }
                $output->writeSetEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->distincts !== null) {
            if (!is_array($this->distincts)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('distincts', TType::SET, 15);
            {
                $output->writeSetBegin(TType::STRUCT, count($this->distincts));
                {
                    foreach ($this->distincts as $iter68 => $iter69)
                    {
                        if (is_scalar($iter69)) {
                            $xfer += $iter68->write($output);
                        } else {
                            $xfer += $iter69->write($output);
                        }
                    }
                }
                $output->writeSetEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->summaries !== null) {
            if (!is_array($this->summaries)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('summaries', TType::SET, 17);
            {
                $output->writeSetBegin(TType::STRUCT, count($this->summaries));
                {
                    foreach ($this->summaries as $iter70 => $iter71)
                    {
                        if (is_scalar($iter71)) {
                            $xfer += $iter70->write($output);
                        } else {
                            $xfer += $iter71->write($output);
                        }
                    }
                }
                $output->writeSetEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->queryProcessorNames !== null) {
            if (!is_array($this->queryProcessorNames)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('queryProcessorNames', TType::LST, 19);
            {
                $output->writeListBegin(TType::STRING, count($this->queryProcessorNames));
                {
                    foreach ($this->queryProcessorNames as $iter72)
                    {
                        $xfer += $output->writeString($iter72);
                    }
                }
                $output->writeListEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->deepPaging !== null) {
            if (!is_object($this->deepPaging)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('deepPaging', TType::STRUCT, 21);
            $xfer += $this->deepPaging->write($output);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->disableFunctions !== null) {
            if (!is_array($this->disableFunctions)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('disableFunctions', TType::MAP, 23);
            {
                $output->writeMapBegin(TType::STRING, TType::STRING, count($this->disableFunctions));
                {
                    foreach ($this->disableFunctions as $kiter73 => $viter74)
                    {
                        $xfer += $output->writeString($kiter73);
                        $xfer += $output->writeString($viter74);
                    }
                }
                $output->writeMapEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->customParam !== null) {
            if (!is_array($this->customParam)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('customParam', TType::MAP, 27);
            {
                $output->writeMapBegin(TType::STRING, TType::STRING, count($this->customParam));
                {
                    foreach ($this->customParam as $kiter75 => $viter76)
                    {
                        $xfer += $output->writeString($kiter75);
                        $xfer += $output->writeString($viter76);
                    }
                }
                $output->writeMapEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->suggest !== null) {
            if (!is_object($this->suggest)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('suggest', TType::STRUCT, 29);
            $xfer += $this->suggest->write($output);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->abtest !== null) {
            if (!is_object($this->abtest)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('abtest', TType::STRUCT, 30);
            $xfer += $this->abtest->write($output);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->userId !== null) {
            $xfer += $output->writeFieldBegin('userId', TType::STRING, 31);
            $xfer += $output->writeString($this->userId);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->rawQuery !== null) {
            $xfer += $output->writeFieldBegin('rawQuery', TType::STRING, 32);
            $xfer += $output->writeString($this->rawQuery);
            $xfer += $output->writeFieldEnd();
        }
        $xfer += $output->writeFieldStop();
        $xfer += $output->writeStructEnd();
        return $xfer;
    }
}
