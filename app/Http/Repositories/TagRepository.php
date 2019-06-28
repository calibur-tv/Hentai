<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\Tag\TagBodyResource;
use App\Http\Transformers\Tag\TagItemResource;
use App\Models\Tag;
use App\User;

class TagRepository extends Repository
{
    public function item($slug, $refresh = false)
    {
        $result = $this->RedisItem("tag:{$slug}", function () use ($slug)
        {
            $tag = Tag
                ::where('slug', $slug)
                ->first();

            if (is_null($tag))
            {
                return 'nil';
            }

            return new TagItemResource($tag);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function relation_item($slug, $refresh = false)
    {
        $result = $this->RedisItem("tag-category:{$slug}", function () use ($slug)
        {
            $tag = Tag
                ::where('slug', $slug)
                ->first();

            if (is_null($tag))
            {
                return 'nil';
            }

            return [
                'tag' => new TagBodyResource($tag),
                'parent' => $tag->parent_slug ? new TagItemResource($tag->parent()->first()) : null,
                'children' => TagItemResource::collection($tag->children()->get())
            ];
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function getMarkedTag($slug, $user)
    {
        $tag = Tag
            ::where('slug', $slug)
            ->first();

        if (is_null($tag))
        {
            return null;
        }

        if (!$user->hasBookmarked($tag))
        {
            return false;
        }

        return $tag;
    }

    public function getMyNotebook($slug, $user)
    {

    }

    public function bookmarks($slug, $refresh = false)
    {
        $result = $this->RedisItem("user-bookmark-tags:{$slug}", function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return 'nil';
            }

            $list = $user
                ->bookmarks(Tag::class)
                ->with('extra')
                ->get()
                ->toArray();

            if (empty($list))
            {
                return [
                    'bangumi' => [],
                    'game' => [],
                    'topic' => [],
                    'notebook' => []
                ];
            }

            $bangumi = [];
            $game = [];
            $topic = [];
            $notebook = [];

            $bangumiSlug = config('app.tag.bangumi');
            $gameSlug = config('app.tag.game');
            $topicSlug = config('app.tag.topic');
            $notebookSlug = config('app.tag.notebook');

            foreach ($list as $item)
            {
                $one = [
                    'slug' => $item['slug'],
                    'avatar' => $item['avatar'],
                    'name' => $item['name'],
                    'extra' => json_decode($item['extra']['text'], true)
                ];

                if ($item['parent_slug'] === $bangumiSlug)
                {
                    $bangumi[] = $one;
                }
                else if ($item['parent_slug'] === $gameSlug)
                {
                    $game[] = $one;
                }
                else if ($item['parent_slug'] === $topicSlug)
                {
                    $topic[] = $one;
                }
                else if ($item['parent_slug'] === $notebookSlug)
                {
                    $notebook[] = $one;
                }
            }

            return [
                'bangumi' => $bangumi,
                'game' => $game,
                'topic' => $topic,
                'notebook' => $notebook
            ];
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }
}
