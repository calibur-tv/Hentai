<?php

namespace App\Console\Jobs;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\Repository;
use App\Http\Repositories\UserRepository;
use App\Models\Pin;
use App\Models\Tag;
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
        $query = new Query();

        $lastPage = Redis::GET('bangumi-fetch-page') ?: 1;

        $bangumi = $query->getBangumiList($lastPage);

        foreach ($bangumi as $item)
        {
            $bangumiExist = DB
                ::table('bangumi_copy')
                ->where('type', 1)
                ->where('name', $item['name'])
                ->count();

            if ($bangumiExist)
            {
                continue;
            }

            DB::table('bangumi_copy')
                ->insert([
                    'type' => 1,
                    'name' => $item['name'],
                    'source_id' => $item['id'],
                    'text' => json_encode($item),
                ]);

            $idols = $query->getBangumiIdols($item['id']);

            foreach ($idols as $idol)
            {
                $idolExist = DB
                    ::table('bangumi_copy')
                    ->where('type', 2)
                    ->where('name', $idol['name'])
                    ->count();

                if ($idolExist)
                {
                    continue;
                }

                DB::table('bangumi_copy')
                    ->insert([
                        'type' => 2,
                        'name' => $idol['name'],
                        'source_id' => $idol['id'],
                        'text' => json_encode($idol),
                    ]);
            }
        }

        Redis::SET('bangumi-fetch-page', (int)$lastPage + 1);

        return true;
    }
}
