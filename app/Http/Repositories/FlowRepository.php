<?php


namespace App\Http\Repositories;


use App\Models\Tag;
use Illuminate\Support\Carbon;

class FlowRepository extends Repository
{
    public $times = [
        'all', '3-day', '7-day', '30-day'
    ];

    public function pins($slug, $sort, $isUp, $specId, $time, $take)
    {
        if ($sort === 'hottest')
        {
            $ids = $this->hottest_ids($slug, $time);
            $idsObj = $this->filterIdsBySeenIds($ids, $specId, $take);
        }
        else if ($sort === 'active')
        {
            $ids = $this->active_ids($slug);
            $idsObj = $this->filterIdsBySeenIds($ids, $specId, $take);
        }
        else
        {
            $ids = $this->newest_ids($slug);
            $idsObj = $this->filterIdsByMaxId($ids, $specId, $take, false, $isUp);
        }

        return $idsObj;
    }

    public function hottest_ids($slug, $time, $refresh = false)
    {
        return $this->RedisSort($this->hottest_cache_key($slug, $time), function () use ($slug, $time)
        {
            $relations = Tag
                ::where('slug', $slug)
                ->with(['pins' => function($query) use ($time)
                {
                    $query
                        ->when($time !== 'all', function ($q) use ($time)
                        {
                            if ($time === '3-day')
                            {
                                $date = Carbon::now()->addDays(-3);
                            }
                            else if ($time === '7-day')
                            {
                                $date = Carbon::now()->addDays(-7);
                            }
                            else if ($time === '30-day')
                            {
                                $date = Carbon::now()->addDays(-30);
                            }
                            else
                            {
                                $date = Carbon::now()->addDays(-1);
                            }
                            return $q->where('created_at', '>=', $date);
                        })
                        ->where('trial_type', 0)
                        ->whereNotIn('content_type', [2])
                        ->whereNotNull('published_at')
                        ->whereNull('last_top_at')
                        ->select('slug', 'visit_count', 'comment_count', 'like_count', 'mark_count', 'reward_count', 'created_at');
                }])
                ->select('id')
                ->get()
                ->toArray();

            $result = [];
            if ($time === '3-day')
            {
                $i = 0.8;
            }
            else if ($time === '7-day')
            {
                $i = 0.5;
            }
            else if ($time === '30-day')
            {
                $i = 0.3;
            }
            else
            {
                $i = 0.1;
            }
            // https://segmentfault.com/a/1190000004253816
            foreach ($relations as $list)
            {
                foreach ($list['pins'] as $pin)
                {
                    $result[$pin['slug']] = (
                        log(($pin['visit_count'] + 1), 10) * 4 +
                        log(($pin['comment_count'] * 4 + 1), M_E) +
                        log(($pin['like_count'] * 2 + $pin['mark_count'] * 3 + $pin['reward_count'] * 10 + 1), 10)
                    ) / pow(((time() - strtotime($pin['created_at'])) + 1), $i);
                }
            }

            return $result;
        }, ['force' => $refresh]);
    }

    public function newest_ids($slug, $refresh = false)
    {
        return $this->RedisSort($this->newest_cache_key($slug), function () use ($slug)
        {
            $relations = Tag
                ::where('slug', $slug)
                ->with(['pins' => function($query)
                {
                    $query
                        ->where('trial_type', 0)
                        ->whereNotIn('content_type', [2])
                        ->whereNull('last_top_at')
                        ->whereNotNull('published_at')
                        ->select('slug', 'published_at')
                        ->orderBy('published_at', 'DESC');
                }])
                ->select('id')
                ->get()
                ->toArray();

            $result = [];
            foreach ($relations as $list)
            {
                foreach ($list['pins'] as $pin)
                {
                    $result[$pin['slug']] = $pin['published_at'];
                }
            }
            return $result;
        }, ['force' => $refresh, 'is_time' => true]);
    }

    public function active_ids($slug, $refresh = false)
    {
        return $this->RedisSort($this->newest_cache_key($slug), function () use ($slug)
        {
            $relations = Tag
                ::where('slug', $slug)
                ->with(['pins' => function($query)
                {
                    $query
                        ->where('trial_type', 0)
                        ->whereNotIn('content_type', [2])
                        ->whereNull('last_top_at')
                        ->whereNotNull('published_at')
                        ->select('slug', 'updated_at')
                        ->orderBy('updated_at', 'DESC');
                }])
                ->select('id')
                ->get()
                ->toArray();

            $result = [];
            foreach ($relations as $list)
            {
                foreach ($list['pins'] as $pin)
                {
                    $result[$pin['slug']] = $pin['updated_at'];
                }
            }
            return $result;
        }, ['force' => $refresh, 'is_time' => true]);
    }

    public function add_pin($tagSlug, $pinSlug)
    {
        $this->SortAdd($this->newest_cache_key($tagSlug), $pinSlug);
        $this->SortAdd($this->active_cache_key($tagSlug), $pinSlug);
    }

    public function del_pin($tagSlug, $pinSlug)
    {
        $this->SortRemove($this->newest_cache_key($tagSlug), $pinSlug);
        $this->SortRemove($this->active_cache_key($tagSlug), $pinSlug);
        foreach ($this->times as $time)
        {
            $this->SortRemove($this->hottest_cache_key($tagSlug, $time), $pinSlug);
        }
    }

    protected function hottest_cache_key(string $slug, $time)
    {
        return "tag-hottest-{$slug}-{$time}";
    }

    protected function newest_cache_key(string $slug)
    {
        return "tag-newest-{$slug}-all";
    }

    protected function active_cache_key(string $slug)
    {
        return "tag-active-{$slug}-all";
    }
}
