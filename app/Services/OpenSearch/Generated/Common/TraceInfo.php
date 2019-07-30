<?php


namespace App\Services\OpenSearch\Generated\Common;


use App\Services\OpenSearch\Thrift\Type\TType;

class TraceInfo
{
    static $_TSPEC;

    /**
     * @var string
     */
    public $requestId = null;
    /**
     * @var string
     */
    public $tracer = null;

    public function __construct($vals=null) {
        if (!isset(self::$_TSPEC)) {
            self::$_TSPEC = array(
                1 => array(
                    'var' => 'requestId',
                    'type' => TType::STRING,
                ),
                3 => array(
                    'var' => 'tracer',
                    'type' => TType::STRING,
                ),
            );
        }
        if (is_array($vals)) {
            if (isset($vals['requestId'])) {
                $this->requestId = $vals['requestId'];
            }
            if (isset($vals['tracer'])) {
                $this->tracer = $vals['tracer'];
            }
        }
    }

    public function getName() {
        return 'TraceInfo';
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
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->requestId);
                    } else {
                        $xfer += $input->skip($ftype);
                    }
                    break;
                case 3:
                    if ($ftype == TType::STRING) {
                        $xfer += $input->readString($this->tracer);
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
        $xfer += $output->writeStructBegin('TraceInfo');
        if ($this->requestId !== null) {
            $xfer += $output->writeFieldBegin('requestId', TType::STRING, 1);
            $xfer += $output->writeString($this->requestId);
            $xfer += $output->writeFieldEnd();
        }
        if ($this->tracer !== null) {
            $xfer += $output->writeFieldBegin('tracer', TType::STRING, 3);
            $xfer += $output->writeString($this->tracer);
            $xfer += $output->writeFieldEnd();
        }
        $xfer += $output->writeFieldStop();
        $xfer += $output->writeStructEnd();
        return $xfer;
    }
}
