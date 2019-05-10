<?php


namespace App\Http\Modules\Cooling;

use App\Http\Repositories\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Cooling
{
    /**
     * Activity constructor.
     *
     * 计算活跃度
     * 每天的活跃度写入一条数据
     * 总的活跃度是每天的相加，并处以今天的时间戳 - 昨天的时间戳（再除以一个定量）
     * 每天晚上走定时任务计算
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    // 每次访问增加
    public function update($id, $score = 1)
    {
        Redis::INCRBYFLOAT($this->todayActivityKey($id), $score);
    }

    // 第二天夜里写表里
    public function migrate($id)
    {
        $timeSeed = date('Y-m-d', strtotime('-1 day'));
        $cacheKey = $this->todayActivityKey($id, $timeSeed);
        $value = Redis::GET($cacheKey);
        if ($value === null)
        {
            return;
        }
        Redis::DEL($cacheKey);
        $value = intval($value);
        if ($value < 0)
        {
            return;
        }
        DB
            ::table($this->table)
            ->insert([
                'model_id' => $id,
                'day' => Carbon::now()->yesterday(),
                'value' => $value,
            ]);
        Redis::DEL($this->table . '_' . $id . '_activities');
        $this->hook($id, $value);
    }

    // 查看当前跃度
    public function get($id, $delta = 0)
    {
        $repository = new Repository();
        return (int)$repository->RedisItem($this->table . '_' . $id . '_activities', function () use ($id, $delta)
        {
            $list = DB
                ::table($this->table)
                ->where('model_id', $id)
                ->where('day', '>', Carbon::now()->addDays(-(31 + $delta)))
                ->select('day', 'value')
                ->get();
            if (empty($list))
            {
                return 0;
            }
            $result = 0;
            $today = strtotime(date('Y-m-d')) - ($delta * 86400);
            foreach ($list as $item)
            {
                $value = intval($item->value);
                if ($value === 0)
                {
                    continue;
                }
                // http://www.ruanyifeng.com/blog/2012/03/ranking_algorithm_newton_s_law_of_cooling.html
                $result += $value / pow((($today - strtotime($item->day)) / 3600), 0.3);
            }
            return $result;
        });
    }

    // 昨天活跃排行
    public function recentIds()
    {
        $repository = new Repository();
        return $repository->RedisList($this->table . '_rencent_activities', function ()
        {
            return DB
                ::table($this->table)
                ->where('value', '>', 0)
                ->where('day', '>', Carbon::now()->addDays(-2))
                ->orderBy('value', 'DESC')
                ->pluck('model_id');
        });
    }

    public function activity($id, $day = 1)
    {
        $today = $this->get($id);
        $target = $this->get($id, $day);
        return $today - $target;
    }

    protected function hook($id, $score)
    {

    }

    protected function todayActivityKey($id, $tail = null)
    {
        return $this->table . '_' . $id . '_activity' . '_' . ($tail ? $tail : date('Y-m-d'));
    }
}
