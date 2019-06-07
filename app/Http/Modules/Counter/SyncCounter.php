<?php


namespace App\Http\Modules\Counter;


use App\Http\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SyncCounter extends Repository
{
    protected $table;
    protected $field;

    /**
     * 使用场景：与数据库同时写，作为数据库读的 middleware，不需要回写数据库
     */
    public function __construct($stateTable, $fieldName = 'model_id')
    {
        $this->table = $stateTable;
        $this->field = $fieldName;
    }

    public function add($slug, $num = 1)
    {
        $cacheKey = $this->cacheKey($slug);
        if (Redis::EXISTS($cacheKey))
        {
            Redis::INCRBY($cacheKey, $num);
        }
        else
        {
            $count = $this->readDB($slug);
            Redis::SET($cacheKey, $count + $num);
        }
    }

    public function get($slug)
    {
        return (int)$this->RedisItem($this->cacheKey($slug), function () use ($slug)
        {
            return $this->readDB($slug);
        });
    }

    public function batchGet($list, $key)
    {
        foreach ($list as $i => $item)
        {
            $list[$i][$key] = $this->get($item['slug']);
        }
        return $list;
    }

    public function deleteCache($slug)
    {
        Redis::DEL($this->cacheKey($slug));
    }

    protected function readDB($slug)
    {
        return DB::table($this->table)
            ->where($this->field, $slug)
            ->count();
    }

    protected function cacheKey($slug)
    {
        return $this->table . '_' . $slug . '_' . $this->field  .'_total';
    }
}
