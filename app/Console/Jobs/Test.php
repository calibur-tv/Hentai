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
         * 1. 从 bangumi_copy 中读番剧
         * 2. 查到响应的 idol
         * 3. 把 idol 写到 tag 表里
         */
        $bangumiList = DB
            ::table('bangumi_copy')
            ->where('type', 1)
            ->whereNotNull('relation_slug')
            ->orderBy('id', 'ASC')
            ->take(100)
            ->get();

        if (empty($bangumiList))
        {
            return true;
        }

        $QShell = new Qshell();
        $creator = User::where('id', 2)->first();
        $idolRoot = Tag::where('slug', config('app.tag.idol'))->first();

        foreach ($bangumiList as $bangumi)
        {
            $idols = DB
                ::table('bangumi_copy')
                ->where('type', 2)
                ->where('relation_slug', $bangumi->source_id)
                ->get();

            foreach ($idols as $idol)
            {
                $tag = Tag::createTag($idol->name, $creator, $idolRoot);

                $extra = json_decode($idol->text);
                $avatar = $QShell->fetch($extra->avatar);
                $tag->updateTag([
                    'avatar' => $avatar,
                    'name' => $idol->name,
                    'intro' => $extra->detail,
                    'alias' => implode(',', $extra->alias)
                ], $creator);
            }

            DB
                ::table('bangumi_copy')
                ->where('id', $bangumi->id)
                ->update([
                    'type' => 3
                ]);
        }

        return true;
    }
}
