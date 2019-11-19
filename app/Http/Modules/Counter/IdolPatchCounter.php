<?php


namespace App\Http\Modules\Counter;


use App\Models\Idol;
use App\Models\Search;

class IdolPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('idols');
    }

    public function boot($slug)
    {
        $idol = Idol
            ::where('slug', $slug)
            ->first();

        if (is_null($idol))
        {
            return [
                'market_price' => 0,
                'stock_price' => 0,
                'fans_count' => 0,
                'coin_count' => 0,
                'stock_count' => 0,
                'rank' => 0
            ];
        }

        return [
            'market_price' => $idol->market_price,
            'stock_price' => $idol->stock_price,
            'fans_count' => $idol->fans_count,
            'coin_count' => $idol->coin_count,
            'stock_count' => $idol->stock_count,
            'rank' => $idol->rank
        ];
    }

    public function search($slug, $result)
    {
        Search
            ::where('slug', $slug)
            ->where('type', 5)
            ->update([
                'score' => $result['market_price']
            ]);
    }
}
