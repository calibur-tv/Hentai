<?php

namespace App\Console\Jobs;

use App\Services\Spider\BangumiSource;
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
        $bangumiSource = new BangumiSource();
        $bangumiSource->updateReleaseBangumi();

        return true;
    }
}
