<?php


namespace App\Http\Modules\Counter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HashCounter
{
    protected $table;
    protected $fields = [];
    protected $timeout = 3600; // 一小时写一次数据库
    protected $uniqueKey;

    public function __construct(string $table, array $fields, string $uniqueKey = 'slug')
    {
        $this->table = $table;
        $this->fields = $fields;
        $this->uniqueKey = $uniqueKey;
    }

    /**
     * 获取所有的值
     */
    public function all($slug)
    {
        $cacheKey = $this->cacheKey($slug);
        $result = Redis::HGETALL($cacheKey);

        if (empty($result))
        {
            $this->save($slug, $this->boot($slug));
            $result = Redis::HGETALL($cacheKey);
        }
        else
        {
            $this->migrate($slug, $result);
        }

        return $result;
    }

    /**
     * 获取某个值
     */
    public function get($slug, $key)
    {
        $cacheKey = $this->cacheKey($slug);
        $result = Redis::HGET($cacheKey, $key);

        if (null === $result)
        {
            $this->save($slug, $this->boot($slug));
            $result = Redis::HGET($cacheKey, $key);
        }
        else
        {
            $this->migrate($slug, $result);
        }

        return $result;
    }

    /**
     * 写入某个值
     */
    public function set($slug, $key, $value)
    {
        $cacheKey = $this->cacheKey($slug);
        if (!Redis::HEXISTS($cacheKey, $key))
        {
            $this->save($slug, $this->boot($slug));
        }

        Redis::HSET($cacheKey, $key, $value);
    }

    /**
     * 加减某个值
     */
    public function add($slug, $key, $value = 1)
    {
        $cacheKey = $this->cacheKey($slug);
        if (!Redis::HEXISTS($cacheKey, $key))
        {
            $this->save($slug, $this->boot($slug));
        }

        Redis::HINCRBYFLOAT($cacheKey, $key, $value);
    }

    /**
     * 从DB初始化数据
     */
    public function boot($slug)
    {
        return DB
            ::table($this->table)
            ->where($this->uniqueKey, $slug)
            ->select($this->fields)
            ->first()
            ->toArray();
    }

    /**
     * 保存数据到缓存
     */
    public function save($slug, $value)
    {
        $value['migrate_at'] = time();

        $key = $this->cacheKey($slug);
        Redis::HMSET($key, $value);
        Redis::EXPIRE($key, daily_cache_expire());
    }

    /**
     * 同步缓存到数据库
     */
    public function migrate(string $slug, array $result)
    {
        if (time() - $result['migrate_at'] < $this->timeout)
        {
            return;
        }

        DB
            ::table($this->table)
            ->where($this->uniqueKey, $slug)
            ->update($result);
    }

    protected function cacheKey($slug)
    {
        return $this->table . '_hash_cache_' . $slug;
    }
}