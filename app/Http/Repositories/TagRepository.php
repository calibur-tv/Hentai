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

    public function relation_item($slug)
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
        });

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
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
                    'topic' => []
                ];
            }

            $bangumi = [];
            $game = [];
            $topic = [];

            $bangumiSlug = config('app.tag.bangumi');
            $gameSlug = config('app.tag.game');
            $topicSlug = config('app.tag.topic');
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
            }

            return [
                'bangumi' => $bangumi,
                'game' => $game,
                'topic' => $topic
            ];
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }
}
