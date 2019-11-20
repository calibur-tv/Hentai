<?php

namespace App\Console\Jobs;

use App\Http\Modules\Counter\IdolPatchCounter;
use App\Http\Modules\VirtualCoinService;
use App\Http\Repositories\IdolRepository;
use App\Models\Idol;
use App\Services\WilsonScoreInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateIdolMarketPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateIdolMarketPrice';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update idol market price';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->updateStockPrice();
        $this->updateMarketPrice();
        $this->updateRank();
        $this->updateCache();

        return true;
    }

    protected function updateStockPrice()
    {
        $total = Idol
            ::sum('coin_count');

        $list = Idol
            ::where('fans_count', '>', 0)
            ->select('slug', 'coin_count')
            ->get();

        $virtualCoinService = new VirtualCoinService();

        foreach ($list as $item)
        {
            $score = $item->coin_count;
            $calc = new WilsonScoreInterval($score, $total - $score);
            $rate = $calc->score();

            DB
                ::table('idols')
                ->where('slug', $item->slug)
                ->update([
                    'stock_price' => $virtualCoinService->calculate($rate * $total / $score + 1)
                ]);
        }
    }

    protected function updateMarketPrice()
    {
        $list = Idol
            ::where('updated_at', '>=', Carbon::now()->addHours(-1))
            ->select('slug', 'stock_price', 'stock_count')
            ->get();

        foreach ($list as $item)
        {
            DB
                ::table('idols')
                ->where('slug', $item->slug)
                ->update([
                    'market_price' => $item->stock_price * $item->stock_count
                ]);
        }
    }

    protected function updateRank()
    {
        $list = Idol
            ::orderBy('market_price', 'DESC')
            ->orderBy('stock_price', 'DESC')
            ->where('fans_count', '>', 0)
            ->select('slug')
            ->get();

        foreach ($list as $index => $item)
        {
            DB
                ::table('idols')
                ->where('slug', $item->slug)
                ->update([
                    'rank' => $index + 1
                ]);
        }
    }

    protected function updateCache()
    {
        $list = Idol
            ::orderBy('market_price', 'DESC')
            ->orderBy('stock_price', 'DESC')
            ->where('fans_count', '>', 0)
            ->pluck('slug')
            ->toArray();

        $idolRepository = new IdolRepository();
        $idolPatchCounter = new IdolPatchCounter();

        foreach ($list as $slug)
        {
            $idolRepository->item($slug, true);
            $idolPatchCounter->all($slug, true);
        }
    }
}
