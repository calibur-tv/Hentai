<?php


namespace App\Services\Spider;


use App\Models\Bangumi;
use App\Models\Idol;
use App\Services\Qiniu\Qshell;

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
}
