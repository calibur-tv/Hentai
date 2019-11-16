<?php

namespace App\Console\Jobs;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\Repository;
use App\Http\Repositories\UserRepository;
use App\Models\Bangumi;
use App\Models\Idol;
use App\Models\IdolExtra;
use App\Models\Pin;
use App\Models\Tag;
use App\Services\OpenSearch\Search;
use App\Services\Qiniu\Qshell;
use App\Services\Spider\BangumiSource;
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
        $bangumi = Bangumi
            ::where('migration_state', 0)
            ->take(1000)
            ->get();

        foreach ($bangumi as $item)
        {
            \App\Models\Search::create([
                'type' => 4,
                'slug' => $item->slug,
                'text' => $item->alias,
                'score' => 0
            ]);

            $item->update([
                'migration_state' => 1
            ]);
        }

        $idol = Idol
            ::where('migration_state', 0)
            ->take(1000)
            ->get();

        foreach ($idol as $item)
        {
            \App\Models\Search::create([
                'type' => 5,
                'slug' => $item->slug,
                'text' => $item->alias,
                'score' => 0
            ]);

            $item->update([
                'migration_state' => 1
            ]);
        }

        return true;
    }
}
