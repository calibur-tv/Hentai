<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Idol;

use App\Http\Repositories\BangumiRepository;
use App\Http\Repositories\UserRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class IdolItemResource extends JsonResource
{
    public function toArray($request)
    {
        $userRepository = new UserRepository();
        $bangumiRepository = new BangumiRepository();

        return [
            'slug' => $this->slug,
            'name' => $this->title,
            'rank' => $this->rank,
            'intro' => $this->intro,
            'avatar' => patchImage($this->avatar, 'default-poster'),
            'is_newbie' => $this->is_newbie,
            'market_price' => $this->market_price,
            'stock_price' => $this->stock_price,
            'fans_count' => $this->fans_count,
            'coin_count' => $this->coin_count,
            'stock_count' => $this->stock_count,
            'buy_coin_count' => 0,
            'buy_stock_count' => 0,
            'lover' => $userRepository->item($this->lover_slug),
            'bangumi' => $bangumiRepository->item($this->bangumi_slug)
        ];
    }
}
