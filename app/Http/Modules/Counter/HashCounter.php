<?php


namespace App\Http\Modules\Counter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HashCounter
{
    protected $table;
    protected $fields = [];
    protected $timeout = 3600; // 一小时写一次数据库
    protected $uniqueKey = 'slug';
    protected $migrate = true;

    public function __construct(string $table, array $fields, bool $migrate = true)
    {
        $this->table = $table;
        $this->fields = $fields;
        $this->migrate = $migrate;
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

        unset($result['migrate_at']);
        $result = array_map(function ($item)
        {
            return (float)$item;
        }, $result);

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

        return (float)$result;
    }

    /**
     * 加减某个值
     */
    public function add($slug, $key, $value = 1, $not_relation = false)
    {
        $cacheKey = $this->cacheKey($slug);
        if (!Redis::HEXISTS($cacheKey, $key))
        {
            $this->save($slug, $this->boot($slug));
            if ($not_relation)
            {
                Redis::HINCRBYFLOAT($cacheKey, $key, $value);
            }
            return;
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
            ->first();
    }

    /**
     * 保存数据到缓存
     */
    public function save($slug, $value)
    {
        $value = gettype($value) === 'array' ? $value : json_decode(json_encode($value), true);

        if ($this->migrate)
        {
            DB
                ::table($this->table)
                ->where($this->uniqueKey, $slug)
                ->update($value);
        }

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
        if (!$this->migrate || time() - $result['migrate_at'] < $this->timeout)
        {
            return;
        }

        unset($result['migrate_at']);

        DB
            ::table($this->table)
            ->where($this->uniqueKey, $slug)
            ->update($result);

        Redis::HMSET(
            $this->cacheKey($slug),
            [
                'migrate_at' => time()
            ]
        );
    }

    protected function cacheKey($slug)
    {
        return $this->table . '_hash_cache_' . $slug;
    }
}
