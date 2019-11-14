<?php

namespace App\Console\Jobs;

use App\Models\IdolExtra;
use App\Models\Tag;
use App\Services\OpenSearch\Search;
use App\Services\Qiniu\Qshell;
use App\Services\Spider\Query;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class GetNewsBangumi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GetNewsBangumi';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get news bangumi';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cacheKey = 'daily-check-news-bangumi-cache';
        $query = new Query();
        $news = $query->getNewsBangumi();

        $cache = Redis::GET($cacheKey);
        if (!$cache)
        {
            Redis::SET($cacheKey, json_encode($news));
            return true;
        }
        $cache = json_decode($cache);

        if (empty($news))
        {
            return true;
        }

        $appendList = array_diff($news, $cache);
        $removeList = array_diff($cache, $news);

        $search = new Search();
        $QShell = new Qshell();
        $creator = User::where('id', 2)->first();
        $bangumiRoot = Tag::where('slug', config('app.tag.bangumi'))->first();
        foreach ($appendList as $item)
        {
            $bangumi = $query->getBangumiDetail($item);
            if (!$bangumi)
            {
                continue;
            }

            $result = $search->retrieve(strtolower($bangumi['name']), 'tag');
            if ($result['total'])
            {
                // TODO 之后删除
                $tag = Tag::where('slug', $result['result'][0]->slug)->first();
                $bangumiCreator = User::where('id', 2)->first();
                $tag->updateTag([
                    'playing' => 1
                ], $bangumiCreator);
                continue;
            }

            $avatar = $QShell->fetch($bangumi['avatar']);
            $tag = Tag::createTag($bangumi['name'], $creator, $bangumiRoot);
            $tag->updateTag([
                'playing' => 1,
                'avatar' => trimImage($avatar),
                'alias' => implode(',', $bangumi['alias']),
                'intro' => $bangumi['detail']
            ], $creator);
        }

        foreach ($removeList as $item)
        {
            $bangumi = $query->getBangumiDetail($item);
            if (!$bangumi)
            {
                continue;
            }

            $result = $search->retrieve(strtolower($bangumi['name']), 'tag');
            if ($result['total'])
            {
                continue;
            }

            $bangumiSlug = $result['result'][0]->slug;
            $bangumiCreator = User::where('id', 2)->first();
            $tag = Tag::where('slug', $bangumiSlug)->first();
            if (is_null($tag))
            {
                continue;
            }
            $tag->updateTag([
                'playing' => 0,
            ], $bangumiCreator);

            $idolSlug = Tag
                ::where('parent_slug', config('app.tag.idol'))
                ->where('creator_slug', $bangumiSlug)
                ->pluck('slug')
                ->toArray();

            IdolExtra
                ::whereIn('idol_slug', $idolSlug)
                ->update([
                    'is_new' => 0
                ]);
        }

        foreach ($news as $item)
        {
            $bangumi = $query->getBangumiDetail($item);
            if (!$bangumi)
            {
                continue;
            }

            $result = $search->retrieve(strtolower($bangumi['name']), 'tag');
            if (!$result['total'])
            {
                continue;
            }

            $idols = $query->getBangumiIdols($item);
            if (empty($idols))
            {
                continue;
            }

            $creator = Tag::where('slug', $result['result'][0]->slug)->first();
            $parent = Tag::where('slug', config('app.tag.bangumi'))->first();
            $isNew = in_array($item, $appendList);
            foreach ($idols as $idol)
            {
                $hasIdol = $search->retrieve(strtolower($idol['name']), 'tag');
                if ($hasIdol['total'])
                {
                    $tag = Tag::where('slug', $hasIdol['result'][0]->slug)->first();
                    $extra = IdolExtra::where('idol_slug', $tag->slug)->first();
                }
                else
                {
                    $avatar = $QShell->fetch($idol['avatar']);
                    $tag = Tag::createTag($idol['name'], $creator, $parent, true);
                    $extra = IdolExtra::create([
                        'idol_slug' => $tag->slug
                    ]);

                    $tag->updateTag([
                        'avatar' => trimImage($avatar),
                        'intro' => $idol['detail'],
                        'alias' => (isset($idol['extra']['简体中文名']) ? $idol['extra']['简体中文名'] . ',' : '') . (isset($idol['extra']['别名']) ? implode(',', $idol['extra']['别名']) : '')
                    ], $creator, true);
                }

                $extra->update([
                    'is_new' => $isNew ? 1 : 0
                ]);
            }
        }

        return true;
    }
}
