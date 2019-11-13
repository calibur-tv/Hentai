<?php

namespace App\Console\Jobs;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\Repository;
use App\Http\Repositories\UserRepository;
use App\Models\IdolExtra;
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
        return true;
    }
}
