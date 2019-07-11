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
        $content = json_decode($this->content->text, true);

        return [
            'tag' => [
                'slug' => $this->slug,
                'deep' => $this->deep,
                'name' => $content['name'],
                'parent_slug' => $this->parent_slug,
                'avatar' => patchImage($content['avatar'], 'default-poster'),
                'alias' => $content['alias'],
                'intro' => $content['intro']
            ],
            'children' => TagItemResource::collection($this->children)
        ];
    }
}
