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
            ::update([
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

    public function retryFailedBangumi()
    {
        $ids = Redis::SMEMBERS('load-bangumi-idol-failed');
        if (!$ids)
        {
            return;
        }
        $id = $ids[0];

        $result = $this->getBangumiIdols($id);
        if ($result)
        {
            Redis::SREM('load-bangumi-idol-failed', $id);
        }
    }

    public function retryFailedIdol()
    {
        $ids = Redis::SMEMBERS('load-idol-failed-ids');
        if (!$ids)
        {
            return;
        }
        $id = $ids[0];

        $result = $this->loadIdolItem($id);
        if ($result)
        {
            Redis::SREM('load-idol-failed-ids', $id);
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

        $this->getBangumiIdols($source['id']);

        return $bangumi;
    }

    public function getBangumiIdols($sourceId)
    {
        $query = new Query();
        $ids = $query->getBangumiIdols($sourceId);
        if (empty($ids))
        {
            Redis::SADD('load-bangumi-idol-failed', $sourceId);
            return false;
        }

        foreach ($ids as $id)
        {
            $this->loadIdolItem($id);
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

    protected function loadIdolItem($id)
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
            Redis::SADD('load-idol-failed-ids', $id);
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

        $avatar = $QShell->fetch($idol['avatar']);

        Idol::create([
            'title' => $idol['name'],
            'avatar' => $avatar,
            'intro' => $idol['intro'],
            'alias' => implode('|', $idol['alias']),
            'source_id' => $idol['id']
        ]);

        return true;
    }
}
