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
            'alias' => explode('|', $this->alias),
            'score' => $this->score,
            'intro' => $this->intro,
            'is_parent' => $this->is_parent,
            'parent_slug' => $this->parent_slug,
            'avatar' => patchImage($this->avatar, 'default-poster')
        ];
    }
}
