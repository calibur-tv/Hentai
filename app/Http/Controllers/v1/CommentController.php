<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\CommentRepository;
use App\Http\Repositories\PinRepository;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;

class CommentController extends Controller
{
    /**
     * 只展示彼此的对话，其他人的不展示
     */
    public function main_item(Request $request)
    {

    }

    /**
     * 时间正序，时间倒序
     * 热度倒序排序
     */
    public function main_list(Request $request)
    {
        $slug = $request->get('slug');
        $sort = $request->get('sort');
        $count = $request->get('count');
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

        $idsObj = $pinRepository->comments($slug, $sort, $count, $specId);
        if (!$idsObj['total'])
        {
            return $this->resOK($idsObj);
        }

        $commentRepository = new CommentRepository();
        $result = $commentRepository->list($idsObj['result']);
        $comments = array_filter($result, function ($item)
        {
           return $item;
        });

        $idsObj['result'] = $comments;

        return $this->resOK($comments);
    }

    public function reply_list(Request $request)
    {

    }

    public function create(Request $request)
    {

    }

    public function reply(Request $request)
    {

    }

    public function destroy(Request $request)
    {

    }

    public function vote(Request $request)
    {

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
