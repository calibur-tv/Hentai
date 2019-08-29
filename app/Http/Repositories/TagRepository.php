<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\Tag\TagResource;
use App\Models\QuestionRule;
use App\Models\Tag;
use App\User;

class TagRepository extends Repository
{
    public function item($slug, $refresh = false)
    {
        if (!$slug)
        {
            return null;
        }

        $result = $this->RedisItem("tag:{$slug}", function () use ($slug)
        {
            $tag = Tag
                ::where('slug', $slug)
                ->with(['content' => function ($query)
                {
                    $query->orderBy('created_at', 'desc');
                }])
                ->first();

            if (is_null($tag))
            {
                return 'nil';
            }

            return new TagResource($tag);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function children($slug, $page, $count = 10, $refresh = false)
    {
        $result = $this->RedisItem("tag-{$slug}-children", function () use ($slug)
        {
            $tag = Tag
                ::where('parent_slug', $slug)
                ->orderBy('activity_stat', 'desc')
                ->orderBy('pin_count', 'desc')
                ->orderBy('followers_count', 'desc')
                ->orderBy('seen_user_count', 'desc')
                ->with(
                    [
                        'content' => function ($query)
                        {
                            $query->orderBy('created_at', 'desc');
                        }
                    ]
                )
                ->get();

            return TagResource::collection($tag);
        }, $refresh);

        if (gettype($result) === 'string')
        {
            $result = json_decode($result, true);
        }

        return $this->filterIdsByPage($result, $page, $count);
    }

    public function rule($slug, $refresh = false)
    {
        return $this->RedisItem("tag-join-rule:{$slug}", function () use ($slug)
        {
            return QuestionRule
                ::where('tag_slug', $slug)
                ->first();
        }, $refresh);
    }

    public function hottest()
    {
        $result = $this->RedisItem('hottest-sub-area', function ()
        {
            $tag = Tag
                ::whereIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.topic'),
                    config('app.tag.game')
                ])
                ->orderBy('activity_stat', 'desc')
                ->orderBy('pin_count', 'desc')
                ->orderBy('followers_count', 'desc')
                ->orderBy('seen_user_count', 'desc')
                ->take(10)
                ->with(
                    [
                        'content' => function ($query)
                        {
                            $query->orderBy('created_at', 'desc');
                        }
                    ]
                )
                ->get();

            return TagResource::collection($tag);
        });

        if (gettype($result) === 'string')
        {
            $result = json_decode($result, true);
        }
        return $result;
    }

    public function search()
    {
        $result = $this->RedisItem('tag-all-search', function ()
        {
            $tag = Tag
                ::whereIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.topic'),
                    config('app.tag.game')
                ])
                ->with(
                    [
                        'content' => function ($query)
                        {
                            $query->orderBy('created_at', 'desc');
                        }
                    ]
                )
                ->get();

            return TagResource::collection($tag);
        });

        if (gettype($result) === 'string')
        {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * @param $slug
     * @param $user
     * @return bool|null|object
     */
    public function checkTagIsMarked($slug, $user)
    {
        $tag = $this->item($slug);

        if (is_null($tag))
        {
            return null;
        }

        if (!$user->hasBookmarked(slug2id($slug), Tag::class))
        {
            return false;
        }

        return $tag;
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
                ->with(['content' => function ($query)
                {
                    $query->orderBy('created_at', 'desc');
                }])
                ->orderBy('activity_stat', 'desc')
                ->orderBy('pin_count', 'desc')
                ->orderBy('followers_count', 'desc')
                ->orderBy('seen_user_count', 'desc')
                ->get();

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

            $list = TagResource::collection($list);

            foreach ($list as $item)
            {
                if ($item['parent_slug'] === $bangumiSlug)
                {
                    $bangumi[] = $item;
                }
                else if ($item['parent_slug'] === $gameSlug)
                {
                    $game[] = $item;
                }
                else if ($item['parent_slug'] === $topicSlug)
                {
                    $topic[] = $item;
                }
                else if ($item['parent_slug'] === $notebookSlug)
                {
                    $notebook[] = $item;
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

    public function receiveTagChain($slug, $result = [])
    {
        if (!$slug)
        {
            return $result;
        }
        $tag = $this->item($slug);
        if (!$tag)
        {
            return $result;
        }

        if (empty($result))
        {
            $result[] = $slug;
        }

        if (!$tag->parent_slug || $tag->parent_slug === config('app.tag.calibur'))
        {
            return $result;
        }
        $result[] = $tag->parent_slug;

        return $this->receiveTagChain($tag->parent_slug, $result);
    }
}
