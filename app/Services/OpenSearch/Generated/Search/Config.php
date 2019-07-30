<?php


namespace App\Services\OpenSearch\Generated\Search;


use App\Services\OpenSearch\Thrift\Exception\TProtocolException;
use App\Services\OpenSearch\Thrift\Type\TType;

class Config
{
    static $_TSPEC;

    /**
     * app name 或 app id 或 app version
     *
     * @var string[]
     */
    public $appNames = null;
    /**
     * @var int
     */
    public $start = 0;
    /**
     * @var int
     */
    public $hits = 15;
    /**
     * @var int
     */
    public $searchFormat =   0;
    /**
     * example:  query=config=start:0,hit:15,rerank_size:200,format:json,KVpairs=name:company_name,price:new_price&&query=id:'489013149'</p>
     *
     * config.setCustomConfig(Lists.newArrayList("mykey1:name#company_name,price#new_price"));
     *
     *
     *
     * @var string[]
     */
    public $customConfig = null;
    /**
     * example: cluster=daogou&&kvpairs=name:company_name&&query=笔筒</p>
     *
     * config.setKvpairs("name:company_name,price:new_price");
     *
     *
     *
     * @var string
     */
    public $kvpairs = null;
    /**
     * 获取搜索结果包含的字段列表(fetch_fields)
     *
     *
     * @var string[]
     */
    public $fetchFields = null;
    /**
     * 分区查询.  routeValue为分区键所对应的值.
     *
     *
     * @var string
     */
    public $routeValue = null;

    public function __construct($vals=null) {
        if (!isset(self::$_TSPEC)) {
            self::$_TSPEC = array(
                1 => array(
                    'var' => 'appNames',
                    'type' => TType::LST,
                    'etype' => TType::STRING,
                    'elem' => array(
                        'type' => TType::STRING,
                    ),
                ),
                2 => array(
                    'var' => 'start',
                    'type' => TType::I32,
                ),
                3 => array(
                    'var' => 'hits',
                    'type' => TType::I32,
                ),
                5 => array(
                    'var' => 'searchFormat',
                    'type' => TType::I32,
                ),
                7 => array(
                    'var' => 'customConfig',
                    'type' => TType::LST,
                    'etype' => TType::STRING,
                    'elem' => array(
                        'type' => TType::STRING,
                    ),
                ),
                9 => array(
                    'var' => 'kvpairs',
                    'type' => TType::STRING,
                ),
                11 => array(
                    'var' => 'fetchFields',
                    'type' => TType::LST,
                    'etype' => TType::STRING,
                    'elem' => array(
                        'type' => TType::STRING,
                    ),
                ),
                13 => array(
                    'var' => 'routeValue',
                    'type' => TType::STRING,
                ),
            );
        }
        if (is_array($vals)) {
            if (isset($vals['appNames'])) {
                $this->appNames = $vals['appNames'];
            }
            if (isset($vals['start'])) {
                $this->start = $vals['start'];
            }
            if (isset($vals['hits'])) {
                $this->hits = $vals['hits'];
            }
            if (isset($vals['searchFormat'])) {
                $this->searchFormat = $vals['searchFormat'];
            }
            if (isset($vals['customConfig'])) {
                $this->customConfig = $vals['customConfig'];
            }
            if (isset($vals['kvpairs'])) {
                $this->kvpairs = $vals['kvpairs'];
            }
            if (isset($vals['fetchFields'])) {
                $this->fetchFields = $vals['fetchFields'];
            }
            if (isset($vals['routeValue'])) {
                $this->routeValue = $vals['routeValue'];
            }
        }
    }

    public function getName() {
        return 'Config';
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
                case 1:
                    if ($ftype == TType::LST) {
                        $this->appNames = array();
                        $_size0 = 0;
                        $_etype3 = 0;
                        $xfer += $input->readListBegin($_etype3, $_size0);
                        for ($_i4 = 0; $_i4 < $_size0; ++$_i4)
                        {
                            $elem5 = null;
                            $xfer += $input->readString($elem5);
                            $this->appNames []= $elem5;
                        }
                        $xfer += $input->readListEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 2:
                    if ($ftype == TType::I32) {
                        $xfer += $input->readI32($this->start);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 3:
                    if ($ftype == TType::I32) {
                        $xfer += $input->readI32($this->hits);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 5:
                    if ($ftype == TType::I32) {
                        $xfer += $input->readI32($this->searchFormat);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 7:
                    if ($ftype == TType::LST) {
                        $this->customConfig = array();
                        $_size6 = 0;
                        $_etype9 = 0;
                        $xfer += $input->readListBegin($_etype9, $_size6);
                        for ($_i10 = 0; $_i10 < $_size6; ++$_i10)
                        {
                            $elem11 = null;
                            $xfer += $input->readString($elem11);
                            $this->customConfig []= $elem11;
                        }
                        $xfer += $input->readListEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 9:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->kvpairs);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 11:
                    if ($ftype == TType::LST) {
                        $this->fetchFields = array();
                        $_size12 = 0;
                        $_etype15 = 0;
                        $xfer += $input->readListBegin($_etype15, $_size12);
                        for ($_i16 = 0; $_i16 < $_size12; ++$_i16)
                        {
                            $elem17 = null;
                            $xfer += $input->readString($elem17);
                            $this->fetchFields []= $elem17;
                        }
                        $xfer += $input->readListEnd();
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 13:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->routeValue);
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
        $xfer += $output->writeStructBegin('Config');
        if ($this->appNames !== null) {
            if (!is_array($this->appNames)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('appNames', TType::LST, 1);
            {
                $output->writeListBegin(TType::STRING, count($this->appNames));
                {
                    foreach ($this->appNames as $iter18)
                    {
                        $xfer += $output->writeString($iter18);
                    }
                }
                $output->writeListEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->start !== null) {
            $xfer += $output->writeFieldBegin('start', TType::I32, 2);
            $xfer += $output->writeI32($this->start);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->hits !== null) {
            $xfer += $output->writeFieldBegin('hits', TType::I32, 3);
            $xfer += $output->writeI32($this->hits);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->searchFormat !== null) {
            $xfer += $output->writeFieldBegin('searchFormat', TType::I32, 5);
            $xfer += $output->writeI32($this->searchFormat);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->customConfig !== null) {
            if (!is_array($this->customConfig)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('customConfig', TType::LST, 7);
            {
                $output->writeListBegin(TType::STRING, count($this->customConfig));
                {
                    foreach ($this->customConfig as $iter19)
                    {
                        $xfer += $output->writeString($iter19);
                    }
                }
                $output->writeListEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->kvpairs !== null) {
            $xfer += $output->writeFieldBegin('kvpairs', TType::STRING, 9);
            $xfer += $output->writeString($this->kvpairs);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->fetchFields !== null) {
            if (!is_array($this->fetchFields)) {
                throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
            }
            $xfer += $output->writeFieldBegin('fetchFields', TType::LST, 11);
            {
                $output->writeListBegin(TType::STRING, count($this->fetchFields));
                {
                    foreach ($this->fetchFields as $iter20)
                    {
                        $xfer += $output->writeString($iter20);
                    }
                }
                $output->writeListEnd();
            }
            $xfer += $output->writeFieldEnd();
        }
        if ($this->routeValue !== null) {
            $xfer += $output->writeFieldBegin('routeValue', TType::STRING, 13);
            $xfer += $output->writeString($this->routeValue);
            $xfer += $output->writeFieldEnd();
        }
        $xfer += $output->writeFieldStop();
        $xfer += $output->writeStructEnd();
        return $xfer;
    }
}
