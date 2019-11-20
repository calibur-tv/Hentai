<?php

namespace App\Console\Jobs;

use App\Models\Idol;
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
        $list = Idol
            ::where('updated_at', '>=', Carbon::now()->addHours(-1))
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

        $list = Idol
            ::orderBy('market_price', 'DESC')
            ->orderBy('stock_price', 'DESC')
            ->where('fans_count', '>', 0)
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

        return true;
    }
}
