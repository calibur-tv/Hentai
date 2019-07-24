<?php


namespace App\Http\Modules\Counter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AsyncCounter
{
    protected $table;
    protected $field;
    protected $timeout = 3600; // 一小时写一次数据库
    protected $force;
    /**
     * 使用场景：需要定期将数据回写数据
     * 1. 但是这里的数据在缓存超时之后，还未写入的情况下就会丢失，导致数据库的数据小于真实值
     * 2. 并发 add 的时候，只会增加一次，导致数据库的数据小于真实值
     */
    public function __construct($tableName, $filedName, $forceMigration = false)
    {
        $this->table = $tableName;
        $this->field = $filedName;
        $this->force = $forceMigration;
    }

    public function add($slug, $num = 1)
    {
        $cacheKey = $this->cacheKey($slug);
        if (Redis::EXISTS($cacheKey))
        {
            $result = Redis::INCRBY($cacheKey, $num);
            $writeKey = $this->writeKey($slug);
            $lastWriteAt = Redis::GET($writeKey);
            if (
                null === $lastWriteAt ||
                time() - $lastWriteAt > $this->timeout
            )
            {
                $this->set($slug, $result);
            }

            return (int)$result;
        }
        $value = $this->readDB($slug);
        $result = $value + $num;
        Redis::SET($cacheKey, $result);

        return (int)$result;
    }

    public function get($slug)
    {
        $cacheKey = $this->cacheKey($slug);
        $value = Redis::GET($cacheKey);
        if (null !== $value)
        {
            return (int)$value;
        }

        $count = $this->readDB($slug);
        if ($this->force)
        {
            $this->setDB($slug, $count);
        }

        $valueKey = $this->cacheKey($slug);
        Redis::SET($valueKey, $count);
        Redis::EXPIREAT($valueKey, daily_cache_expire());

        $timeoutKey = $this->writeKey($slug);
        Redis::SET($timeoutKey, time());
        Redis::EXPIRE($timeoutKey, $this->timeout);

        return $count;
    }

    public function batchGet($list, $key)
    {
        foreach ($list as $i => $item)
        {
            $list[$i][$key] = $this->get($item['slug']);
        }
        return $list;
    }

    protected function set($slug, $result)
    {
        $this->setDB($slug, $result);

        $timeoutKey = $this->writeKey($slug);
        Redis::SET($timeoutKey, time());
        Redis::EXPIRE($timeoutKey, $this->timeout);
    }

    protected function setDB($slug, $result)
    {
        DB
            ::table($this->table)
            ->where('slug', $slug)
            ->update([
                $this->field => $result
            ]);
    }

    protected function readDB($slug)
    {
        return (int)DB
            ::table($this->table)
            ->where('slug', $slug)
            ->pluck($this->field)
            ->first();
    }

    protected function cacheKey($slug)
    {
        return $this->table . '_' . $slug . '_' . $this->field;
    }

    protected function writeKey($slug)
    {
        return $this->table . '_' . $slug . '_' . $this->field . '_' . 'last_add_at';
    }
}
