<?php

namespace App\Services\OpenSearch\Generated\Search;

use App\Services\OpenSearch\Thrift\Type\TType;

class Rank
{
    static $_TSPEC;

    /**
     * @var int
     */
    public $reRankSize = 200;
    /**
     * 设置粗排表达式名称
     *
     *
     * @var string
     */
    public $firstRankName = null;
    /**
     * 设置粗排表达式名称
     *
     *
     * @var string
     */
    public $secondRankName = null;

    public function __construct($vals=null) {
        if (!isset(self::$_TSPEC)) {
            self::$_TSPEC = array(
                1 => array(
                    'var' => 'reRankSize',
                    'type' => TType::I32,
                ),
                3 => array(
                    'var' => 'firstRankName',
                    'type' => TType::STRING,
                ),
                5 => array(
                    'var' => 'secondRankName',
                    'type' => TType::STRING,
                ),
            );
        }
        if (is_array($vals)) {
            if (isset($vals['reRankSize'])) {
                $this->reRankSize = $vals['reRankSize'];
            }
            if (isset($vals['firstRankName'])) {
                $this->firstRankName = $vals['firstRankName'];
            }
            if (isset($vals['secondRankName'])) {
                $this->secondRankName = $vals['secondRankName'];
            }
        }
    }

    public function getName() {
        return 'Rank';
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
                    if ($ftype == TType::I32) {
                        $xfer += $input->readI32($this->reRankSize);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 3:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->firstRankName);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 5:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->secondRankName);
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
        $xfer += $output->writeStructBegin('Rank');
        if ($this->reRankSize !== null) {
            $xfer += $output->writeFieldBegin('reRankSize', TType::I32, 1);
            $xfer += $output->writeI32($this->reRankSize);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->firstRankName !== null) {
            $xfer += $output->writeFieldBegin('firstRankName', TType::STRING, 3);
            $xfer += $output->writeString($this->firstRankName);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->secondRankName !== null) {
            $xfer += $output->writeFieldBegin('secondRankName', TType::STRING, 5);
            $xfer += $output->writeString($this->secondRankName);
            $xfer += $output->writeFieldEnd();
        }
        $xfer += $output->writeFieldStop();
        $xfer += $output->writeStructEnd();
        return $xfer;
    }
}
