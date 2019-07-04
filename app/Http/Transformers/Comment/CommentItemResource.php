<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Tag;

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
            'getter' => new UserItemResource($this->getter),
            'author' => new UserItemResource($this->author),
            'content' => $content,
            'trial_type' => $this->trial_type,
            'like_count' => $this->like_count,
            'created_at' => $this->created_at
        ];
    }
}
