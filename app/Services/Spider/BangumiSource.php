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

            $this->importBangumi($info);
        }

        Idol
            ::whereNotIn('bangumi_id', $newIds)
            ->update([
                'is_newbie' => 0
            ]);

        Idol
            ::whereIn('bangumi_id', $newIds)
            ->update([
                'is_newbie' => 1
            ]);
    }

    public function loadHottestBangumi()
    {
        $page = Redis::GET('load-hottest-bangumi-page') ?: 1;
        $fetchFailed = false;
        if ($page > 200)
        {
            $pages = Redis::SMEMBERS('load-bangumi-failed-page');
            if (!$pages)
            {
                return;
            }
            $fetchFailed = true;
            $page = $pages[0];
        }

        $this->getHottestBangumi($page);

        if ($fetchFailed)
        {
            Redis::SREM('load-bangumi-failed-page', $page);
        }
        else
        {
            Redis::SET('load-hottest-bangumi-page', intval($page) + 1);
        }
    }

    public function retryFailedBangumiIdols()
    {
        $ids = Redis::SMEMBERS('load-bangumi-idol-failed');
        if (!$ids)
        {
            return;
        }
        $arr = implode('##', $ids[0]);

        $result = $this->getBangumiIdols($arr[0], $arr[1]);
        if ($result)
        {
            Redis::SREM('load-bangumi-idol-failed', $ids[0]);
        }
    }

    public function retryFailedIdolDetail()
    {
        $ids = Redis::SMEMBERS('load-idol-failed-ids');
        if (!$ids)
        {
            return;
        }
        $arr = implode('##', $ids[0]);

        $result = $this->loadIdolItem($arr[0], $arr[1]);
        if ($result)
        {
            Redis::SREM('load-idol-failed-ids', $ids[0]);
        }
    }

    public function importBangumi($source)
    {
        if (!$source || !$source['name'])
        {
            return null;
        }

        $bangumi = Bangumi
            ::where('source_id', $source['id'])
            ->first();

        if ($bangumi)
        {
            return null;
        }

        $QShell = new Qshell();
        $bangumi = Bangumi
            ::create([
                'title' => $source['name'],
                'avatar' => $QShell->fetch($source['avatar']),
                'intro' => $source['intro'],
                'alias' => implode('|', $source['alias']),
                'source_id' => $source['id']
            ]);

        $bangumiSlug = id2slug($bangumi->id);
        $bangumi->update([
            'slug' => $bangumiSlug
        ]);

        $this->getBangumiIdols($source['id'], $bangumiSlug);

        return $bangumi;
    }

    public function getBangumiIdols($sourceId, $bangumiSlug)
    {
        $query = new Query();
        $ids = $query->getBangumiIdols($sourceId);
        if (empty($ids))
        {
            Redis::SADD('load-bangumi-idol-failed', "{$sourceId}##{$bangumiSlug}");
            return false;
        }

        foreach ($ids as $id)
        {
            $this->loadIdolItem($id, $bangumiSlug);
        }

        return true;
    }

    protected function getHottestBangumi($page)
    {
        $query = new Query();
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

            $this->importBangumi($item);
        }
    }

    protected function loadIdolItem($id, $bangumiSlug)
    {
        if (!$id)
        {
            return true;
        }

        $query = new Query();
        $QShell = new Qshell();
        $idol = $query->getIdolDetail($id);
        if (!$idol)
        {
            Redis::SADD('load-idol-failed-ids', "{$id}##{$bangumiSlug}");
            return false;
        }

        if (!$idol['name'])
        {
            return false;
        }

        $has = Idol
            ::where('source_id', $idol['id'])
            ->first();

        if ($has)
        {
            return true;
        }

        $idol = Idol::create([
            'title' => $idol['name'],
            'avatar' => $QShell->fetch($idol['avatar']),
            'intro' => $idol['intro'],
            'source_id' => $idol['id'],
            'alias' => implode('|', $idol['alias']),
            'bangumi_slug' => $bangumiSlug
        ]);

        $idol->update([
            'slug' => id2slug($idol->id)
        ]);

        return true;
    }
}
