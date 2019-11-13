<?php

namespace App\Console\Jobs;

use App\Models\Tag;
use App\Services\OpenSearch\Search;
use App\Services\Qiniu\Qshell;
use App\Services\Spider\Query;
use App\User;
use Illuminate\Console\Command;

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
        $query = new Query();
        $news = $query->getNewsBangumi();
        if (empty($news))
        {
            return true;
        }
        $search = new Search();
        $QShell = new Qshell();
        $creator = User::where('id', 2)->first();
        $bangumiRoot = Tag::where('slug', config('app.tag.bangumi'))->first();
        foreach ($news as $item)
        {
            if (!$item['name'])
            {
                continue;
            }

            $result = $search->retrieve(strtolower($item['name']), 'tag');
            if ($result['total'])
            {
                continue;
            }

            $bangumi = $query->getBangumiDetail($item['id']);
            $avatar = $QShell->fetch($bangumi['avatar']);
            $tag = Tag::createTag($bangumi['name'], $creator, $bangumiRoot, true);
            $tag->updateTag([
                'playing' => 1,
                'avatar' => trimImage($avatar),
                'alias' => implode(',', $bangumi['alias']),
                'intro' => $bangumi['detail']
            ]);
        }
        return true;
    }
}
