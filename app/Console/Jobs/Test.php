<?php

namespace App\Console\Jobs;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\Repository;
use App\Http\Repositories\UserRepository;
use App\Models\Pin;
use App\Models\Tag;
use App\Services\OpenSearch\Search;
use App\Services\Qiniu\Qshell;
use App\Services\Spider\Query;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test job';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * 1. 从 bangumi_copy 读番剧数据
         * 2. 如果这个番剧有 idol 就继续，如果没有就标记
         * 3. 到 search 里查有没有相似的
         * 4. 如果有相似的，更新相似的，如果没有，就创建
         * 5. 然后标记 bangumi_copy 和 tags 里的数据，设为已 migration
         * 6. 把 bangumi_copy 中 idol 类别的 relation_slug 更新一下
         */
        $bangumiList = DB
            ::table('bangumi_copy')
            ->where('type', 1)
            ->whereNull('relation_slug')
            ->orderBy('id', 'ASC')
            ->take(100)
            ->get();

        if (empty($bangumiList))
        {
            return true;
        }

        $search = new Search();
        $QShell = new Qshell();
        $creator = User::where('id', 2)->first();
        $bangumiRoot = config('app.tag.bangumi');

        foreach ($bangumiList as $bangumi)
        {
            $idolCount = DB
                ::table('bangumi_copy')
                ->where('type', 2)
                ->where('relation_slug', $bangumi->source_id)
                ->count();

            if (!$idolCount)
            {
                DB
                    ::table('bangumi_copy')
                    ->where('id', $bangumi->id)
                    ->update([
                        'relation_slug' => ''
                    ]);

                continue;
            }

            $hasBangumi = $search->retrieve($bangumi->name, 'tag');
            if ($hasBangumi['total'])
            {
                $tag = Tag::where('slug', $hasBangumi['result'][0]->slug)->first();
            }
            else
            {
                $tag = Tag::createTag($bangumi->name, $creator, $bangumiRoot);
            }

            $extra = json_decode($bangumi->text);
            $avatar = $QShell->fetch($extra->avatar);
            $tag->updateTag([
                'avatar' => $avatar,
                'name' => $bangumi->name,
                'intro' => $extra->detail,
                'alias' => implode(',', $extra->alias)
            ], $creator);

            DB
                ::table('bangumi_copy')
                ->where('id', $bangumi->id)
                ->update([
                    'relation_slug' => $tag->slug
                ]);
        }
        return true;
    }
}
