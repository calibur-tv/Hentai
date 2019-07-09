<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2017/12/28
 * Time: 上午7:52
 */

namespace App\Http\Repositories;


use Illuminate\Support\Facades\Redis;

class Repository
{
    public function list($ids, $refresh = false)
    {
        if (empty($ids))
        {
            return [];
        }

        $result = [];
        foreach ($ids as $id)
        {
            $item = $this->item($id, $refresh);
            if ($item)
            {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function RedisItem($key, $func, $force = false, $exp = 'd')
    {
        $cache = $force ? null : Redis::GET($key);
        if (!is_null($cache))
        {
            return preg_match('/^({"|\[\\\\")/', $cache) ? json_decode($cache) : $cache;
        }

        $cache = $func();
        if (is_null($cache))
        {
            return null;
        }

        $type = gettype($cache);
        $encodeCache = $cache;
        if ($type === 'object' || $type === 'array')
        {
            $encodeCache = json_encode($cache, JSON_UNESCAPED_UNICODE);
        }

        if (Redis::SETNX('lock_'.$key, 1))
        {
            Redis::pipeline(function ($pipe) use ($key, $encodeCache, $exp)
            {
                $pipe->EXPIRE('lock_'.$key, 10);
                $pipe->SET($key, $encodeCache);
                $pipe->EXPIREAT($key, $this->expire($exp));
                $pipe->DEL('lock_'.$key);
            });
        }

        return $cache;
    }

    public function RedisHash($key, $func, $force = false, $exp = 'd')
    {
        $cache = $force ? null : Redis::HGETALL($key);

        if (!empty($cache))
        {
            return $cache;
        }

        $cache = $func();

        if (is_null($cache))
        {
            return null;
        }

        if (Redis::SETNX('lock_'.$key, 1))
        {
            Redis::pipeline(function ($pipe) use ($key, $cache, $exp)
            {
                $pipe->EXPIRE('lock_'.$key, 10);
                $pipe->HMSET($key, gettype($cache) === 'array' ? $cache : $cache->toArray());
                $pipe->EXPIREAT($key, $this->expire($exp));
                $pipe->DEL('lock_'.$key);
            });
        }

        return $cache;
    }

    public function RedisList($key, $func, $force = false, $exp = 'd')
    {
        $cache = $force ? [] : Redis::LRANGE($key, 0, -1);

        if (!empty($cache))
        {
            return $cache;
        }

        $cache = $func();
        $cache = gettype($cache) === 'array' ? $cache : $cache->toArray();

        if (empty($cache))
        {
            return [];
        }

        if (Redis::SETNX('lock_'.$key, 1))
        {
            Redis::pipeline(function ($pipe) use ($key, $cache, $exp)
            {
                $pipe->EXPIRE('lock_'.$key, 10);
                $pipe->DEL($key);
                $pipe->RPUSH($key, $cache);
                $pipe->EXPIREAT($key, $this->expire($exp));
                $pipe->DEL('lock_'.$key);
            });
        }

        return $cache;
    }

    public function RedisSort($key, $func, array $opts = [])
    {
        $opts = array_merge([
            'is_time' => false,
            'with_score' => false,
            'exp' => 'd',
            'desc' => true,
            'force' => false
        ], $opts);

        if ($opts['force'])
        {
            $cache = [];
        }
        else
        {
            if ($opts['desc'])
            {
                $cache = $opts['with_score'] ? Redis::ZREVRANGE($key, 0, -1, 'WITHSCORES') : Redis::ZREVRANGE($key, 0, -1);
            }
            else
            {
                $cache = $opts['with_score'] ? Redis::ZRANGE($key, 0, -1, 'WITHSCORES') : Redis::ZRANGE($key, 0, -1);
            }
        }

        if (!empty($cache))
        {
            return $cache;
        }

        $cache = $func();
        $cache = gettype($cache) === 'array' ? $cache : $cache->toArray();

        if (empty($cache))
        {
            return [];
        }

        if ($opts['is_time'])
        {
            foreach ($cache as $i => $item)
            {
                $cache[$i] = gettype($item) === 'string' ? strtotime($item) : $item->timestamp;
            }
        }

        if (Redis::SETNX('lock_'.$key, 1))
        {
            Redis::pipeline(function ($pipe) use ($key, $cache, $opts)
            {
                $pipe->EXPIRE('lock_'.$key, 10);
                $pipe->DEL($key);
                $pipe->ZADD($key, $cache);
                $pipe->EXPIREAT($key, $this->expire($opts['exp']));
                $pipe->DEL('lock_'.$key);
            });
        }

        if ($opts['desc'])
        {
            arsort($cache);
        }
        else
        {
            asort($cache);
        }

        return $opts['with_score'] ? $cache : array_keys($cache);
    }

    public function ListInsertBefore($key, $value)
    {
        if (Redis::EXISTS($key))
        {
            Redis::LPUSHX($key, $value);
        }
    }

    public function ListInsertAfter($key, $value)
    {
        if (Redis::EXISTS($key))
        {
            Redis::RPUSHX($key, $value);
        }
    }

    public function ListRemove($key, $value, $count = 1)
    {
        Redis::LREM($key, $count, $value);
    }

    public function SortAdd($key, $value, $score = 0)
    {
        if (Redis::EXISTS($key))
        {
            if ($score)
            {
                Redis::ZINCRBY($key, $score, $value);
            }
            else
            {
                Redis::ZADD($key, strtotime('now'), $value);
            }
        }
    }

    public function SortRemove($key, $value)
    {
        Redis::ZREM($key, $value);
    }

    public function filterIdsByMaxId($ids, $maxId, $take, $withScore = false, $isUp = false)
    {
        if (empty($ids))
        {
            return [
                'result' => [],
                'total' => 0,
                'no_more' => true
            ];
        }

        $list = $withScore ? array_keys($ids) : $ids;
        if ($maxId)
        {
            if (array_last($list) < $maxId)
            {
                $offset = count($list) + 1;
            }
            else
            {
                $offset = array_search($maxId, $list) + 1;
            }
        }
        else
        {
            $offset = 0;
        }

        $total = count($ids);
        if ($isUp)
        {
            if ($offset < $take + 1)
            {
                $result = array_slice($ids, 0, $offset - 1, $withScore);
            }
            else
            {
                $result = array_slice($ids, $offset - 1 - $take, $take, $withScore);
            }
        }
        else
        {
            $result = array_slice($ids, $offset, $take, $withScore);
        }

        return [
            'result' => $result,
            'total' => $total,
            'no_more' => $isUp ? ($offset <= $take + 1) : ($result > 0 ? ($total - ($offset + $take) <= 0) : true)
        ];
    }

    public function filterIdsBySeenIds($ids, $seenIds, $take, $withScore = false)
    {
        if (empty($ids))
        {
            return [
                'result' => [],
                'total' => 0,
                'no_more' => true
            ];
        }

        $total = count($ids);
        if ($withScore)
        {
            foreach ($ids as $key => $val)
            {
                if (in_array($key, $seenIds))
                {
                    unset($ids[$key]);
                }
            }
            $result = array_slice($ids, 0, $take, true);
        }
        else
        {
            $result = array_slice(array_diff($ids, $seenIds), 0, $take);
        }

        return [
            'result' => $result,
            'total' => $total,
            'no_more' => empty($seenIds) ? $total <= $take : count($result) < $take
        ];
    }

    public function filterIdsByPage($ids, $page, $take, $withScore = false)
    {
        $ids = gettype($ids) === 'string' ? explode(',', $ids) : $ids;

        if (empty($ids))
        {
            return [
                'result' => [],
                'total' => 0,
                'no_more' => true
            ];
        }

        $result = array_slice($ids, $page * $take, $take, $withScore);
        $total = count($ids);

        return [
            'result' => $result,
            'total' => $total,
            'no_more' => $total - ($page + 1) * $take <= 0
        ];
    }

    private function expire($type = 'd')
    {
        if (gettype($type) === 'integer')
        {
            return $type;
        }
        /**
         * d：缓存一天，第二天凌晨的 1 ~ 3 点删除
         * h：缓存一小时
         * m：缓存五分钟
         */
        $day = daily_cache_expire();
        $hour = time() + 3600;
        $minute = time() + 300;

        if ($type === 'd')
        {
            return $day;
        }
        else if ($type === 'h')
        {
            return $hour;
        }
        else if ($type === 'm')
        {
            return $minute;
        }

        return $day;
    }
}
