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

    public function add($id, $num = 1)
    {
        $this->id = $id;
        $cacheKey = $this->cacheKey($id);
        if (Redis::EXISTS($cacheKey))
        {
            Redis::INCRBY($cacheKey, $num);
        }
        return $this->get($id);
    }

    public function get($id)
    {
        return (int)$this->RedisItem($this->cacheKey($id), function () use ($id)
        {
            return $this->migration($id);
        });
    }

    public function batchGet($list, $key)
    {
        foreach ($list as $i => $item)
        {
            $list[$i][$key] = $this->get($item['id']);
        }
        return $list;
    }

    public function deleteCache($id)
    {
        Redis::DEL($this->cacheKey($id));
    }

    protected function migration($id)
    {
        return DB::table($this->table)
            ->where($this->field, $id)
            ->count();
    }

    protected function cacheKey($id)
    {
        return $this->table . '_' . $id . '_' . $this->field  .'_total';
    }
}
