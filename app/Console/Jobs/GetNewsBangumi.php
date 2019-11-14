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
        foreach ($news as $index => $item)
        {
            $bangumi = $query->getBangumiDetail($item);
            $result = $search->retrieve(strtolower($bangumi['name']), 'tag');
            if ($result['total'])
            {
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
        return true;
    }
}
