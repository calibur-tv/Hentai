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
                ->with(['author', 'getter', 'content'])
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

    public function flow($slug, $sort, $mode, $page, $count, $specId)
    {
        if ($sort === 'hottest')
        {
            $ids = $this->hottest_comment($slug);
            $idsObj = $mode === 'jump'
                ? $this->filterIdsByPage($ids, $page, $count)
                : $this->filterIdsBySeenIds($ids, $specId, $count);
        }
        else
        {
            $ids = $this->timeline_comment($slug, $sort);
            $idsObj = $mode === 'jump'
                ? $this->filterIdsByPage($ids, $page, $count)
                : $this->filterIdsByMaxId($ids, $specId, $count);
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
                ->select('like_count', 'created_at', 'id')
                ->get()
                ->toArray();

            $result = [];
            foreach ($list as $row)
            {
                $result[$row['id']] = $row['like_count'] + (int)(strtotime($row['created_at']) / 10000);
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
