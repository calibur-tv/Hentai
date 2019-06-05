<?php


namespace App\Http\Modules\Counter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AsyncCounter
{
    protected $table;
    protected $field;
    protected $timeout = 3600; // 一小时写一次数据库
    /**
     * 使用场景：需要定期将数据回写数据，没有关联表
     * 1. 但是这里的数据在缓存超时之后，还未写入的情况下就会丢失，导致数据库的数据小于真实值
     * 2. 并发 add 的时候，只会增加一次，导致数据库的数据小于真实值
     */
    public function __construct($tableName, $filedName)
    {
        $this->table = $tableName;
        $this->field = $filedName;
    }

    public function add($id, $num = 1)
    {
        $cacheKey = $this->cacheKey($id);
        if (Redis::EXISTS($cacheKey))
        {
            $result = Redis::INCRBY($cacheKey, $num);
            $writeKey = $this->writeKey($id);
            $lastWriteAt = Redis::GET($writeKey);
            if (
                null === $lastWriteAt ||
                time() - $lastWriteAt > $this->timeout
            )
            {
                $this->set($id, $result);
            }

            return $result;
        }
        $value = $this->readDB($id);
        $result = $value + $num;
        $result = Redis::SET($cacheKey, $result);

        return $result;
    }

    public function get($id)
    {
        $cacheKey = $this->cacheKey($id);
        $value = Redis::GET($cacheKey);
        if (null !== $value)
        {
            return $value;
        }

        $count = $this->readDB($id);

        $valueKey = $this->cacheKey($id);
        Redis::SET($valueKey, $count);
        Redis::EXPIRE($valueKey, strtotime(date('Y-m-d'), time()) + 86400 + rand(3600, 10800));

        $timeoutKey = $this->writeKey($id);
        Redis::SET($timeoutKey, time());
        Redis::EXPIRE($timeoutKey, $this->timeout);

        return $count;
    }

    public function batchGet($list, $key)
    {
        foreach ($list as $i => $item)
        {
            $list[$i][$key] = (int)$this->get($item['id']);
        }
        return $list;
    }

    protected function set($id, $result)
    {
        $this->setDB($id, $result);

        $timeoutKey = $this->writeKey($id);
        Redis::SET($timeoutKey, time());
        Redis::EXPIRE($timeoutKey, $this->timeout);
    }

    protected function setDB($id, $result)
    {
        DB
            ::table($this->table)
            ->where('id', $id)
            ->update([
                $this->field => $result
            ]);
    }

    protected function readDB($id)
    {
        return DB
            ::table($this->table)
            ->where('id', $id)
            ->pluck($this->field)
            ->first();
    }

    protected function cacheKey($id)
    {
        return $this->table . '_' . $id . '_' . $this->field;
    }

    protected function writeKey($id)
    {
        return $this->table . '_' . $id . '_' . $this->field . '_' . 'last_add_at';
    }
}
