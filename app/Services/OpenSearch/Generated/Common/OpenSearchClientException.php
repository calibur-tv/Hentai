<?php


namespace App\Services\OpenSearch\Generated\Common;


use App\Services\OpenSearch\Thrift\Exception\TException;
use App\Services\OpenSearch\Thrift\Type\TType;

class OpenSearchClientException extends TException
{
    static $_TSPEC;

    public function __construct() {
        if (!isset(self::$_TSPEC)) {
            self::$_TSPEC = array(
            );
        }
    }

    public function getName() {
        return 'OpenSearchClientException';
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
        $xfer += $output->writeStructBegin('OpenSearchClientException');
        $xfer += $output->writeFieldStop();
        $xfer += $output->writeStructEnd();
        return $xfer;
    }
}
