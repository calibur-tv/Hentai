<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Tag;

use Illuminate\Http\Resources\Json\JsonResource;

class TagMetaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name
        ];
    }
}
