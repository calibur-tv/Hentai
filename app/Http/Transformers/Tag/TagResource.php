<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Tag;

use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray($request)
    {
        $content = json_decode($this->content->text, true);

        return [
            'slug' => $this->slug,
            'name' => $content['name'],
            'avatar' => patchImage($content['avatar'], 'default-poster'),
            'intro' => $content['intro'],
            'alias' => $content['alias'],
            'creator_slug' => $this->creator_slug,
            'parent_slug' => $this->parent_slug,
            'pin_count' => $this->pin_count,
            'seen_user_count' => $this->seen_user_count,
            'activity_stat' => $this->activity_stat,
            'followers_count' => $this->followers_count,
            'question_count' => $this->question_count
        ];
    }
}
