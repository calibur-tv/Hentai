<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers;

use App\Models\Image;
use Illuminate\Http\Resources\Json\JsonResource;

class PinResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->parseContent(),
            'is_locked' => $this->is_locked,
            'is_secret' => $this->is_secret,
            'trial_type' => $this->trial_type,
            'comment_type' => $this->comment_type,
            'recommended_at' => $this->recommended_at,
            'last_top_at' => $this->last_top_at,
            'publish_at' => $this->published_at ?: $this->created_at,
            'author' => $this->author,
            'tags' => $this->tags
        ];
    }

    protected function parseContent()
    {
        $content = $this
            ->content()
            ->pluck('text')
            ->first();

        if (is_null($content))
        {
            return [];
        }

        $content = json_decode($content, true);
        $imageArr = [];

        foreach ($content as $i => $item)
        {
            if ($item['type'] === 'img')
            {
                $imageArr[$i] = $item['id'];
            }
        }

        $imageIndex = array_keys($imageArr);
        $imageIds = array_values($imageArr);
        $imageIdsStr = implode(',', $imageIds);

        $images = Image
            ::whereIn('id', $imageIds)
            ->orderByRaw("FIELD(id, $imageIdsStr)")
            ->get();

        $images = ImageResource::collection($images);

        foreach ($imageIndex as $i => $index)
        {
            $content[$index] = $images[$i];
        }

        return $content;
    }
}
