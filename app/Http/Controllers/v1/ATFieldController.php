<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use App\Models\Pin;
use App\Models\QuestionRule;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ATFieldController extends Controller
{
    public function getJoinRule(Request $request)
    {
        $slug = $request->get('slug');
        if (!$slug)
        {
            return $this->resErrBad();
        }

        $tagRepository = new TagRepository();
        $rule = $tagRepository->rule($slug);

        return $this->resOK($rule);
    }

    public function updateJoinRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_slug' => 'required|string',
            'question_count' => 'required|integer|max:100|min:1',
            'right_rate' => 'required|integer|max:100|min:50',
            'qa_minutes' => 'required|integer|max:120|min:30',
            'rule_type' => 'required|integer',
            'result_type' => 'required|integer',
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $user = $request->user();

        if ($user->cant('update_tag_join_rule') && !$user->is_admin)
        {
            return $this->resErrRole();
        }

        $resultType = $request->get('result_type');
        QuestionRule
            ::where('tag_slug', $request->get('tag_slug'))
            ->update([
                'question_count' => $request->get('question_count'),
                'right_rate' => $resultType === 1 ? 100 : $request->get('right_rate'),
                'qa_minutes' => $request->get('qa_minutes'),
                'rule_type' => $request->get('rule_type'),
                'result_type' => $resultType
            ]);

        $tagRepository = new TagRepository();
        $tagRepository->rule($request->get('tag_slug'), true);

        return $this->resNoContent();
    }

    public function createQA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_slug' => 'required|string',
            'title' => 'required|string|max:50',
            'answers' => 'required|array',
            'right_index' => 'required|integer|min:0|max:3'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $user = $request->user();
        $tag = Tag
            ::where('slug', $request->get('tag_slug'))
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound();
        }

        if (
            $user->cant('create_tag_qa') &&
            !$user->is_admin &&
            !$user->hasBookmarked($tag, Tag::class)
        )
        {
            return $this->resErrRole();
        }

        $content = [
            [
                'type' => 'title',
                'data' => [
                    'text' => $request->get('title')
                ]
            ]
        ];

        $content[] = [
            'type' => 'vote',
            'data' => [
                'items' => $request->get('answers'),
                'right_ids' => $request->get('right_index'),
                'max_select' => 1,
                'expired_at' => 0
            ]
        ];

        $contentType = 2;
        $visitType = 0;
        $tags = [
            config('app.tag.qa'),
            $tag->slug
        ];

        $qa = Pin::createPin(
            $content,
            $contentType,
            $visitType,
            $user,
            $tags
        );

        return $this->resCreated($qa);
    }

    public function deleteQA(Request $request)
    {
        $user = $request->user();
        if (!$user->is_admin)
        {
            return $this->resErrRole();
        }

        $slug = $request->get('slug');
        $pin = Pin
            ::where('content_type', 2)
            ->where('slug', $slug)
            ->first();

        if (is_null($pin))
        {
            return $this->resNoContent();
        }

        $pin->deletePin($user);

        return $this->resNoContent();
    }

    public function recommendQA(Request $request)
    {
        $user = $request->user();
        if (!$user->is_admin)
        {
            return $this->resErrRole();
        }

        $slug = $request->get('slug');
        $pin = Pin
            ::where('content_type', 2)
            ->where('slug', $slug)
            ->first();

        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        $pin->update([
            'recommended_at' => Carbon::now()
        ]);

        return $this->resNoContent();
    }

    public function flow(Request $request)
    {
        $user = $request->user();
        if (!$user->is_admin)
        {
            return $this->resErrRole();
        }

        $page = $request->get('page') ?: 1;
        $take = $request->get('count') ?: 10;
        $slug = $request->get('slug') ?: '';
        $sort = $request->get('sort') ?: 'newest';

        $ids = Pin
            ::where('content_type', 2)
            ->when($sort === 'newest', function ($query)
            {
                return $query
                    ->whereNull('recommended_at')
                    ->orderBy('id', 'ASC');
            }, function ($query) use ($sort)
            {
                return $query
                    ->whereNotNull('recommended_at')
                    ->orderBy('like_count', $sort === 'like_desc' ? 'DESC' : 'ASC');
            })
            ->when($slug, function ($query) use ($slug)
            {
                return $query->whereHas('tags', function ($q) use ($slug)
                {
                    $q->where('slug', $slug);
                });
            })
            ->take($take)
            ->skip(($page - 1) * $take)
            ->pluck('slug')
            ->toArray();

        $pinRepository = new PinRepository();
        $result = $pinRepository->list($ids);

        return $this->resOK([
            'result' => $result,
            'no_more' => count($result) < $take,
            'total' => 0
        ]);
    }

    public function beginQA(Request $request)
    {

    }

    public function checkQA(Request $request)
    {

    }

    public function Questions(Request $request)
    {

    }

    public function showQA(Request $request)
    {

    }
}
