<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers;

use App\Http\Modules\RichContentService;
use App\Http\Transformers\Tag\TagMetaResource;
use App\Http\Transformers\User\UserItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PinResource extends JsonResource
{
    public function toArray($request)
    {
        $richContentService = new RichContentService();

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $richContentService->parseRichContent($this->content->text),
            'author' => new UserItemResource($this->author),
            'tags' => TagMetaResource::collection($this->tags),
            'visit_type' => $this->visit_type,
            'trial_type' => $this->trial_type,
            'content_type' => $this->content_type,
            'comment_type' => $this->comment_type,
            'last_top_at' => $this->last_top_at,
            'recommended_at' => $this->recommended_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
