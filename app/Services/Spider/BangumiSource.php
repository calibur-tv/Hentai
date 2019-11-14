<?php


namespace App\Services\Spider;


use App\Models\Bangumi;
use App\Models\Idol;
use App\Services\Qiniu\Qshell;
use Illuminate\Support\Facades\Redis;

class BangumiSource
{
    public function updateReleaseBangumi()
    {
        $query = new Query();
        $QShell = new Qshell();
        $newIds = $query->getNewsBangumi();
        foreach ($newIds as $id)
        {
            $bangumi = Bangumi
                ::where('source_id', $id)
                ->first();

            if ($bangumi)
            {
                continue;
            }

            $info = $query->getBangumiDetail($id);
            if (!$info)
            {
                Redis::SADD('load-bangumi-failed-ids', $id);
                continue;
            }

            if (!$info['name'])
            {
                continue;
            }

            $avatar = $QShell->fetch($info['avatar']);

            Bangumi::create([
                'title' => $info['name'],
                'avatar' => $avatar,
                'intro' => $info['intro'],
                'alias' => implode('|', $info['alias']),
                'source_id' => $id
            ]);

            $this->getBangumiIdols($id);
        }

        Idol
            ::whereIn('bangumi_id', $newIds)
            ->update([
                'is_newbie' => 1
            ]);

        Idol
            ::whereNotIn('bangumi_id', $newIds)
            ->update([
                'is_newbie' => 0
            ]);
    }

    public function loadHottestBangumi()
    {
        $page = Redis::GET('load-hottest-bangumi-page') ?: 1;
        if ($page > 200)
        {
            return;
        }
        $query = new Query();
        $QShell = new Qshell();
        $list = $query->getBangumiList($page);

        if (empty($list))
        {
            Redis::SADD('load-bangumi-failed-page', $page);
        }

        foreach ($list as $item)
        {
            if (!$item)
            {
                Redis::SADD('load-bangumi-failed-ids', $id);
                continue;
            }

            if (!$item['name'])
            {
                continue;
            }

            $bangumi = Bangumi
                ::where('source_id', $item['id'])
                ->first();

            if ($bangumi)
            {
                continue;
            }

            $avatar = $QShell->fetch($item['avatar']);
            Bangumi
                ::create([
                    'title' => $item['name'],
                    'avatar' => $avatar,
                    'intro' => $item['intro'],
                    'alias' => implode('|', $item['alias']),
                    'source_id' => $item['id']
                ]);

            $this->getBangumiIdols($item['id']);
        }

        Redis::SET('load-hottest-bangumi-page', intval($page) + 1);
    }

    protected function getBangumiIdols($bangumiId)
    {
        $query = new Query();
        $QShell = new Qshell();
        $ids = $query->getBangumiIdols($bangumiId);
        if (empty($ids))
        {
            Redis::SADD('load-bangumi-idol-failed', $bangumiId);
        }

        foreach ($ids as $id)
        {
            $idol = $query->getIdolDetail($id);
            if (!$idol)
            {
                Redis::SADD('load-idol-failed-ids', $id);
                continue;
            }

            if (!$idol['name'])
            {
                continue;
            }

            $has = Idol
                ::where('source_id', $idol['id'])
                ->first();

            if ($has)
            {
                continue;
            }

            $avatar = $QShell->fetch($idol['avatar']);

            Idol::create([
                'title' => $idol['name'],
                'avatar' => $avatar,
                'intro' => $idol['intro'],
                'alias' => implode('|', $idol['alias']),
                'source_id' => $idol['id']
            ]);
        }
    }
}
