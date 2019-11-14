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
            if (!$info || !$info['name'])
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

        foreach ($list as $item)
        {
            if (!$item || !$item['name'])
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

    protected function getBangumiIdols($id)
    {
        $query = new Query();
        $QShell = new Qshell();
        $idols = $query->getBangumiIdols($id);
        foreach ($idols as $idol)
        {
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
