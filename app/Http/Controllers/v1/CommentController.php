<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\CommentRepository;
use App\Http\Repositories\PinRepository;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * 例如展示一个答案页面
     */
    public function show(Request $request)
    {

    }

    /**
     * 时间升序（time_asc），时间降序（time_desc）
     * 热度倒序排序（hottest）
     */
    public function list(Request $request)
    {
        $slug = $request->get('slug');
        $sort = $request->get('sort');
        $mode = $request->get('mode') ?: 'flow';
        $page = $request->get('page') ?: 1;
        $count = $request->get('count') ?: 10;
        if ($sort === 'hottest')
        {
            $specId = $request->get('seen_ids') ? explode(',', $request->get('seen_ids')) : [];
        }
        else
        {
            $specId = $request->get('last_id');
        }

        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($slug);
        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        $commentRepository = new CommentRepository();
        $idsObj = $commentRepository->flow($slug, $sort, $mode, $page - 1, $count, $specId);
        if (!$idsObj['total'])
        {
            return $this->resOK($idsObj);
        }

        $result = $commentRepository->list($idsObj['result']);
        $comments = array_filter($result, function ($item)
        {
           return $item;
        });

        $idsObj['result'] = $comments;

        return $this->resOK($idsObj);
    }

    /**
     * 只展示彼此的对话，其他人的不展示
     */
    public function talk(Request $request)
    {

    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000|min:2',
            'images' => 'present|array',
            'pin_slug' => 'required|string',
            'comment_id' => 'present|integer',
            'images.*.url' => 'required|string',
            'images.*.width' => 'required|integer',
            'images.*.height' => 'required|integer',
            'images.*.size' => 'required|integer',
            'images.*.mime' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $images = $request->get('images');
        $pinSlug = $request->get('pin_slug');

        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($pinSlug);
        if (is_null($pin))
        {
            return $this->resErrNotFound('不存在的文章');
        }

        if ($pin->comment_type != 0)
        {
            return $this->resErrRole('没有评论权限');
        }

        $commentRepository = new CommentRepository();
        $commentId = $request->get('comment_id');

        if ($commentId)
        {
            $targetComment = $commentRepository->item($commentId);
            if (is_null($targetComment))
            {
                return $this->resErrNotFound('不存在的评论');
            }

            if ($targetComment->pin_slug !== $pinSlug)
            {
                return $this->resErrBad();
            }

            $targetUserSlug = $targetComment->author->slug;
        }
        else
        {
            $targetUserSlug = $pin->author->slug;
        }

        $user = $request->user();
        if ($targetUserSlug === $user->slug)
        {
            $targetUserSlug = '';
        }

        $content = [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => $request->get('content')
                ]
            ],
        ];
        foreach ($images as $image)
        {
            array_push($content, [
                'type' => 'image',
                'data' => [
                    'file' => $image,
                    'caption' => '',
                    'stretched' => false,
                    'withBackground' => false,
                    'withBorder' => false
                ]
            ]);
        }

        $comment = Comment::createComment(
            $content,
            $pinSlug,
            $targetUserSlug,
            $user
        );
        if (is_null($comment))
        {
            return $this->resErrBad();
        }

        return $this->resOK($commentRepository->item($comment->id));
    }

    /**
     * 删除评论
     */
    public function delete(Request $request)
    {
        $commentId = $request->get('comment_id');

        $comment = Comment
            ::where('id', $commentId)
            ->first();
        if (is_null($comment))
        {
            return $this->resErrNotFound();
        }

        $user = $request->user();
        if ($comment->from_user_slug != $user->slug)
        {
            return $this->resErrRole();
        }

        $comment->deleteComment($user);

        return $this->resNoContent();
    }

    public function trials(Request $request)
    {

    }

    public function resolve(Request $request)
    {

    }

    public function reject(Request $request)
    {

    }
}
