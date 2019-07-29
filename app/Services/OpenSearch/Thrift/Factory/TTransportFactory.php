<?php

namespace App\Services\OpenSearch\Thrift\Factory;

use App\Services\OpenSearch\Thrift\Transport\TTransport;

class TTransportFactory
{
  /**
   * @static
   * @param TTransport $transport
   * @return TTransport
   */
  public static function getTransport(TTransport $transport)
  {
    return $transport;
  }
}
