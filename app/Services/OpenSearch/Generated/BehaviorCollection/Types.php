<?php
namespace App\Services\OpenSearch\Generated\BehaviorCollection;
/**
 * Autogenerated by Thrift Compiler (0.9.3)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use App\Services\OpenSearch\Thrift\Base\TBase;
use App\Services\OpenSearch\Thrift\Type\TType;
use App\Services\OpenSearch\Thrift\Type\TMessageType;
use App\Services\OpenSearch\Thrift\Exception\TException;
use App\Services\OpenSearch\Thrift\Exception\TProtocolException;
use App\Services\OpenSearch\Thrift\Protocol\TProtocol;
use App\Services\OpenSearch\Thrift\Protocol\TBinaryProtocolAccelerated;
use App\Services\OpenSearch\Thrift\Exception\TApplicationException;


final class Command {
  const ADD = 0;
  static public $__names = array(
    0 => 'ADD',
  );
}

final class Constant extends \App\Services\OpenSearch\Thrift\Type\TConstant {
  static protected $DOC_KEY_CMD;
  static protected $DOC_KEY_FIELDS;

  static protected function init_DOC_KEY_CMD() {
    return "cmd";
  }

  static protected function init_DOC_KEY_FIELDS() {
    return "fields";
  }
}


