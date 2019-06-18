<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Tag;

use Illuminate\Http\Resources\Json\JsonResource;

class TagBodyResource extends JsonResource
{
    public function toArray($request)
    {
        $extra = $this->extra()->pluck('text')->first();
        $extra = json_decode($extra, true);

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'parent_slug' => $this->parent_slug,
            'alias' => $extra['alias'],
            'intro' => $extra['intro'] ?: '暂无简介'
        ];
    }
}
