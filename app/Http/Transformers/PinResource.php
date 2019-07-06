<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers;

use App\Http\Modules\RichContentService;
use App\Http\Transformers\Tag\TagItemResource;
use App\Http\Transformers\User\UserItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PinResource extends JsonResource
{
    public function toArray($request)
    {
        $richContentService = new RichContentService();

        $title = [
            'banner' => null,
            'text' => ''
        ];

        $content = $richContentService->parseRichContent($this->content->text);
        if ($content[0]['type'] === 'title')
        {
            $title = array_shift($content)['data'];
        }

        return [
            'slug' => $this->slug,
            'title' => $title,
            'content' => $content,
            'media' => $richContentService->parseRichPoster($title, $content),
            'intro' => $richContentService->paresPureContent($content),
            'author' => new UserItemResource($this->author),
            'area' => new TagItemResource($this->area),
            'notebook' => new TagItemResource($this->notebook),
            'visit_type' => $this->visit_type,
            'trial_type' => $this->trial_type,
            'content_type' => $this->content_type,
            'comment_type' => $this->comment_type,
            'last_top_at' => $this->last_top_at,
            'last_edit_at' => $this->last_edit_at,
            'recommended_at' => $this->recommended_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'visit_count' => $this->visit_count,
            'comment_count' => $this->comment_count,
            'like_count' => $this->upvoters()->count(),
            'mark_count' => $this->mark_count,
            'reward_count' => $this->reward_count,
            'up_vote_status' => false,
            'down_vote_status' => false,
            'mark_status' => false,
            'reward_status' => false
        ];
    }
}
