<?php

namespace App\Console\Jobs;

use App\Models\IdolExtra;
use App\Models\Tag;
use App\Services\OpenSearch\Search;
use App\Services\Qiniu\Qshell;
use App\Services\Spider\BangumiSource;
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
        $bangumiSource = new BangumiSource();
        $bangumiSource->updateReleaseBangumi();

        return true;
    }
}
