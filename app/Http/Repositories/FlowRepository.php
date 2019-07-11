<?php


namespace App\Http\Repositories;


use App\Models\Tag;
use Illuminate\Support\Carbon;

class FlowRepository extends Repository
{
    public $times = [
        'all', '3-day', '7-day', '30-day'
    ];

    public function pins($tags, $sort, $isUp, $specId, $time, $take)
    {
        if ($sort === 'hottest')
        {
            $ids = $this->hottest_ids($tags, $time);
            $idsObj = $this->filterIdsBySeenIds($ids, $specId, $take);
        }
        else if ($sort === 'active')
        {
            $ids = $this->active_ids($tags);
            $idsObj = $this->filterIdsBySeenIds($ids, $specId, $take);
        }
        else
        {
            $ids = $this->newest_ids($tags);
            $idsObj = $this->filterIdsByMaxId($ids, $specId, $take, false, $isUp);
        }

        return $idsObj;
    }

    public function hottest_ids($tags, $time, $refresh = false)
    {
        return $this->RedisSort($this->hottest_cache_key($tags, $time), function () use ($tags, $time)
        {
            $relations = Tag
                ::whereIn('slug', $tags)
                ->where('pin_count', '>', 0)
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
                        ->where('visit_type', 0)
                        ->where('trial_type', 0)
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

    public function newest_ids($tags, $refresh = false)
    {
        return $this->RedisSort($this->newest_cache_key($tags), function () use ($tags)
        {
            $relations = Tag
                ::whereIn('slug', $tags)
                ->where('pin_count', '>', 0)
                ->with(['pins' => function($query)
                {
                    $query
                        ->where('visit_type', 0)
                        ->where('trial_type', 0)
                        ->whereNull('last_top_at')
                        ->select('slug', 'created_at');
                }])
                ->select('id')
                ->get()
                ->toArray();

            $result = [];
            foreach ($relations as $list)
            {
                foreach ($list['pins'] as $pin)
                {
                    $result[$pin['slug']] = $pin['created_at'];
                }
            }
            return $result;
        }, ['force' => $refresh, 'is_time' => true]);
    }

    public function active_ids($tags, $refresh = false)
    {
        return $this->RedisSort($this->newest_cache_key($tags), function () use ($tags)
        {
            $relations = Tag
                ::whereIn('slug', $tags)
                ->where('pin_count', '>', 0)
                ->with(['pins' => function($query)
                {
                    $query
                        ->where('visit_type', 0)
                        ->where('trial_type', 0)
                        ->whereNull('last_top_at')
                        ->select('slug', 'updated_at');
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

    public function add_pin($tag, $slug)
    {
        if (!$tag)
        {
            return;
        }

        $i = 0;
        $tagRepository = new TagRepository();
        $loopTag = $tag;
        while ($i < $tag->deep)
        {
            $tags = $tagRepository->getChildrenSlugByLoop($loopTag->slug, $this->tag_flow_max_loop($loopTag->deep));

            $this->SortAdd($this->newest_cache_key($tags), $slug);
            $this->SortAdd($this->active_cache_key($tags), $slug);

            $loopTag = $tagRepository->item($loopTag->parent_slug);

            $i++;
        }
    }

    public function del_pin($tag, $slug)
    {
        if (!$tag)
        {
            return;
        }

        $i = 0;
        $tagRepository = new TagRepository();
        $loopTag = $tag;
        while ($i < $tag->deep)
        {
            $tags = $tagRepository->getChildrenSlugByLoop($loopTag->slug, $this->tag_flow_max_loop($loopTag->deep));

            $this->SortRemove($this->newest_cache_key($tags), $slug);
            $this->SortRemove($this->active_cache_key($tags), $slug);
            foreach ($this->times as $time)
            {
                $this->SortRemove($this->hottest_cache_key($tags, $time), $slug);
            }

            $loopTag = $tagRepository->item($loopTag->parent_slug);

            $i++;
        }
    }

    public function tag_flow_max_loop($deep)
    {
        return 3 - $deep;
    }

    protected function hottest_cache_key(array $tags, $time)
    {
        sort($tags);
        $tags = implode($tags, '-');
        return "tag-hottest-{$tags}-{$time}";
    }

    protected function newest_cache_key(array $tags)
    {
        sort($tags);
        $tags = implode($tags, '-');
        return "tag-newest-{$tags}-all";
    }

    protected function active_cache_key(array $tags)
    {
        sort($tags);
        $tags = implode($tags, '-');
        return "tag-active-{$tags}-all";
    }
}
