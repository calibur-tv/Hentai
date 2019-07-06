<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Comment;

use App\Http\Modules\RichContentService;
use App\Http\Transformers\User\UserItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentItemResource extends JsonResource
{
    public function toArray($request)
    {
        $richContentService = new RichContentService();
        $content = $richContentService->parseRichContent($this->content->text);

        return [
            'id' => $this->id,
            'pin_slug' => $this->pin_slug,
            'getter' => new UserItemResource($this->getter),
            'author' => new UserItemResource($this->author),
            'content' => $content,
            'trial_type' => $this->trial_type,
            'like_count' => $this->upvoters()->count(),
            'up_vote_status' => false,
            'down_vote_status' => false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
