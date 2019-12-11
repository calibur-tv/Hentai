<?php

namespace App\Console\Jobs;

use App\Http\Modules\Counter\IdolPatchCounter;
use App\Http\Modules\VirtualCoinService;
use App\Http\Repositories\BangumiRepository;
use App\Http\Repositories\IdolRepository;
use App\Models\Bangumi;
use App\Models\Idol;
use App\Services\WilsonScoreInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateBangumiRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateBangumiRank';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update bangumi rank';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $list = Bangumi
            ::where('score', '>', '0')
            ->orderBy('score', 'DESC')
            ->pluck('slug')
            ->toArray();

        $bangumiRepository = new BangumiRepository();
        foreach ($list as $index => $slug)
        {
            Bangumi
                ::where('slug')
                ->update([
                    'rank' => $index + 1
                ]);

            $bangumiRepository->item($slug, true);
        }

        return true;
    }
}
