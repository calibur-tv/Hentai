<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\Comment\CommentItemResource;
use App\Models\Comment;
use App\Models\Pin;

class CommentRepository extends Repository
{
    public function item($id, $refresh = true)
    {
        $result = $this->RedisItem("comment:{$id}", function () use ($id)
        {
            $comment = Comment
                ::withTrashed()
                ->where('id', $id)
                ->with(['author', 'getter', 'content' => function ($query)
                {
                    $query->orderBy('created_at', 'desc');
                }])
                ->first();

            if (is_null($comment))
            {
                return 'nil';
            }

            return new CommentItemResource($comment);
        }, $refresh);

        if ('nil' === $result)
        {
            return null;
        }

        if ($result->deleted_at != null)
        {
            return null;
        }

        if ($result->trial_type != 0)
        {
            return null;
        }

        return $result;
    }

    public function flow($slug, $sort, $count, $specId, $refresh = false)
    {
        if ($refresh)
        {
            $this->hottest_comment($slug, true);
            $this->timeline_comment($slug, $sort, true);
            return [];
        }

        if ($sort === 'hottest')
        {
            $ids = $this->hottest_comment($slug);
            $idsObj = $this->filterIdsBySeenIds($ids, $specId, $count);
        }
        else
        {
            $ids = $this->timeline_comment($slug, $sort);
            $idsObj = $this->filterIdsByMaxId($ids, $specId, $count);
        }

        return $idsObj;
    }

    private function hottest_comment($slug, $refresh = false)
    {
        return $this->RedisSort($this->hottest_comment_cache_key($slug), function () use ($slug)
        {
            $pin = Pin
                ::where('slug', $slug)
                ->first();

            if (is_null($pin))
            {
                return [];
            }

            $list = $pin
                ->comments()
                ->orderBy('like_count', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->select('like_count', 'created_at', 'id')
                ->get()
                ->toArray();

            $result = [];
            foreach ($list as $row)
            {
                $result[$row['id']] = $row['like_count'] * 10000000000 + strtotime($row['created_at']);
            }

            return $result;
        }, ['force' => $refresh]);
    }

    private function timeline_comment($slug, $sort, $refresh = false)
    {
        $ids = $this->RedisList($this->timeline_comment_cache_key($slug), function () use ($slug)
        {
            $pin = Pin
                ::where('slug', $slug)
                ->first();

            if (is_null($pin))
            {
                return [];
            }

            return $pin
                ->comments()
                ->orderBy('created_at', 'ASC')
                ->pluck('id')
                ->toArray();
        }, $refresh);

        if ($sort === 'time_desc')
        {
            return array_reverse($ids);
        }

        return $ids;
    }

    public function hottest_comment_cache_key($slug)
    {
        return "pin:{$slug}:comments-hottest";
    }

    public function timeline_comment_cache_key($slug)
    {
        return "pin:{$slug}:comments-timeline";
    }
}
