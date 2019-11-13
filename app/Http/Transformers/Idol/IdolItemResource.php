<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Idol;

use App\Http\Repositories\TagRepository;
use App\Http\Repositories\UserRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class IdolItemResource extends JsonResource
{
    public function toArray($request)
    {
        $content = json_decode($this->content->text, true);
        $userRepository = new UserRepository();
        $tagRepository = new TagRepository();

        return [
            'slug' => $this->slug,
            'name' => $content['name'],
            'intro' => $content['intro'],
            'avatar' => patchImage($content['avatar'], 'default-poster'),
            'market_price' => $this->extra->market_price,
            'stock_price' => $this->extra->stock_price,
            'fans_count' => $this->extra->fans_count,
            'coin_count' => $this->extra->coin_count,
            'lover' => $userRepository->item($this->extra->lover_user_slug),
            'bangumi' => $tagRepository->item($this->creator_slug)
        ];
    }
}
