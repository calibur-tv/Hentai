<?php


namespace App\Http\Modules\Counter;


use App\Models\Comment;

class CommentLikeCounter extends AsyncCounter
{
    public function __construct()
    {
        parent::__construct('comments', 'like_count', true);
    }

    public function setDB($id, $result)
    {
        $comment = Comment
            ::where('id', $id)
            ->first();

        $comment->update([
            $this->field => $comment->upvoters()->count()
        ]);
    }

    public function readDB($id)
    {
        $comment = Comment
            ::where('id', $id)
            ->first();

        return $comment->upvoters()->count();
    }
}
