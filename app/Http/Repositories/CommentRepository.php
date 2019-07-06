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

        if ($result->trial_type != 0)
        {
            return null;
        }

        return $result;
    }
}
