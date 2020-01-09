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
        $media = $richContentService->parseRichPoster($title, $content);
        $badge = '帖子';
        if ($richContentService->getFirstType($content, 'baidu'))
        {
            $badge = '网盘';
        }
        else if ($richContentService->getFirstType($content, 'vote'))
        {
            $badge = '投票';
        }
        else if ($richContentService->getFirstType($content, 'music'))
        {
            $badge = '音乐';
        }
        else if ($richContentService->getFirstType($content, 'video'))
        {
            $badge = '视频';
        }
        else if ($media['image_count'] >= 9)
        {
            $badge = '图集';
        }

        return [
            'slug' => $this->slug,
            'title' => $title,
            'badge' => $badge,
            'content' => $content,
            'media' => $media,
            'intro' => $richContentService->paresPureContent($content),
            'author' => new UserItemResource($this->author),
            'area' => new TagItemResource($this->area),
            'topic' => new TagItemResource($this->topic),
            'notebook' => new TagItemResource($this->notebook),
            'main_notebook_slug' => $this->main_notebook_slug,
            'trial_type' => $this->trial_type,
            'content_type' => $this->content_type,
            'comment_type' => $this->comment_type,
            'last_top_at' => $this->last_top_at,
            'recommended_at' => $this->recommended_at,
            'created_at' => $this->created_at,
            'last_edit_at' => $this->last_edit_at,
            'published_at' => $this->published_at,
            'deleted_at' => $this->deleted_at,
            'visit_count' => $this->visit_count,
            'comment_count' => $this->comment_count,
            'like_count' => $this->upvoters()->count(),
            'mark_count' => $this->mark_count,
            'reward_count' => $this->reward_count,
            'can_up' => $this->can_up
        ];
    }
}
