<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Bangumi;

use Illuminate\Http\Resources\Json\JsonResource;

class BangumiItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'slug' => $this->slug,
            'name' => $this->title,
            'score' => $this->score,
            'avatar' => patchImage($this->avatar, 'default-poster')
        ];
    }
}
