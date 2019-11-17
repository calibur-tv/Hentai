<?php

namespace App\Console\Jobs;

use App\Models\Idol;
use Illuminate\Console\Command;

class UpdateIdolRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateIdolRank';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update idol rank';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $list = Idol
            ::orderBy('market_price', 'DESC')
            ->orderBy('stock_price', 'DESC')
            ->where('fans_count', '>', 0)
            ->get();

        foreach ($list as $index => $item)
        {
            $item->update([
                'rank' => $index + 1
            ]);
        }

        return true;
    }
}
