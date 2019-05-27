<?php


namespace App\Http\Modules\DailyRecord;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DailyRecord
{
    private $table = 'daily_records';

    public function __construct($record_type)
    {
        $this->record_type = $record_type;
        /**
         * 1. 用户的活跃度
         * 2. 用户的曝光量
         * 3. 标签的发文量
         * 4. 标签的点击量
         * 5. 文章的曝光量
         */
    }

    public function set($id, $score = 1)
    {
        Redis::INCRBY($this->setCacheKey($id), $score);
    }

    public function get($id, $delta = 0)
    {
        $cacheKey = $this->getCacheKey($id, $delta);
        $value = Redis::GET($cacheKey);
        if ($value !== null)
        {
            return (int)$value;
        }

        $list = DB
            ::table($this->table)
            ->where('record_id', $id)
            ->where('record_type', $this->record_type)
            ->where('day', '>', Carbon::now()->addDays(-(31 + $delta)))
            ->select('day', 'value')
            ->get();

        $result = 0;
        if (!empty($list))
        {
            $theDay = strtotime(date('Y-m-d')) - ($delta * 86400);
            foreach ($list as $item)
            {
                $value = intval($item->value);
                if ($value === 0)
                {
                    continue;
                }
                // http://www.ruanyifeng.com/blog/2012/03/ranking_algorithm_newton_s_law_of_cooling.html
                $result += $value / pow((($theDay - strtotime($item->day)) / 3600), 0.3);
            }
        }

        Redis::SET($cacheKey, $result);
        Redis::EXPIREAT($cacheKey, strtotime(date('Y-m-d'), time()) + 86400 + rand(3600, 10800));

        return $result;
    }

    public function migrate($id)
    {
        $timeSeed = date('Y-m-d', strtotime('-1 day'));
        $cacheKey = $this->setCacheKey($id, $timeSeed);
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
                'record_id' => $id,
                'record_type' => $this->record_type,
                'day' => Carbon::now()->yesterday(),
                'value' => $value,
            ]);

        $this->hook($id, $value);
    }

    protected function hook($id, $score)
    {

    }

    protected function getCacheKey($id, $delta)
    {
        return 'daily_record_' . $this->record_type . '_' . $id . '_get_' . $delta . '_' . date('Y-m-d');
    }

    protected function setCacheKey($id, $tail = null)
    {
        return 'daily_record_' . $this->record_type . '_' . $id . '_set_' . ($tail ? $tail : date('Y-m-d'));
    }
}
