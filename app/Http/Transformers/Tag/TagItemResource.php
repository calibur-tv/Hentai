<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Tag;

use Illuminate\Http\Resources\Json\JsonResource;

class TagItemResource extends JsonResource
{
    public function toArray($request)
    {
        $content = json_decode($this->content->text, true);

        return [
            'slug' => $this->slug,
            'name' => $content['name'],
            'avatar' => patchImage($content['avatar'], 'default-poster')
        ];
    }
}
