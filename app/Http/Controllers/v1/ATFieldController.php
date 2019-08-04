<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use App\Models\Pin;
use App\Models\PinAnswer;
use App\Models\QuestionRule;
use App\Models\QuestionSheet;
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

    public function create(Request $request)
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

        if (!in_array(
            $tag->parent_slug,
            [
                config('app.tag.bangumi'),
                config('app.tag.game'),
                config('app.tag.newbie')
            ]
        )) {
            return $this->resErrBad('暂不支持开放的分区');
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

    public function delete(Request $request)
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

    public function recommend(Request $request)
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

        $pin->timeline()->create([
            'event_type' => 4,
            'event_slug' => $user->slug
        ]);

        $pinRepository = new PinRepository();
        $pinRepository->item($slug, true);

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

    /**
     * 发卷
     */
    public function begin(Request $request)
    {
        $user = $request->user();
        $slug = $request->get('slug');

        $sheet = QuestionSheet
            ::where('user_slug', $user->slug)
            ->where('tag_slug', $slug)
            ->first();

        if ($sheet)
        {
            return $this->resOK($sheet->result_type == 0 ? 'pending' : 'resolve');
        }

        $rule = QuestionRule
            ::where('tag_slug', $slug)
            ->first();

        if (is_null($rule))
        {
            return $this->resOK('no_rule');
        }

        $pins = Pin
            ::where('content_type', 2)
            ->whereHas('tags', function ($query) use ($slug)
            {
                $query->where('slug', $slug);
            })
            ->take($rule->question_count)
            ->whereNotNull('recommended_at')
            ->pluck('slug')
            ->toArray();

        if (count($pins) < $rule->question_count)
        {
            return $this->resOK('no_question');
        }

        QuestionSheet::create([
            'user_slug' => $user->slug,
            'tag_slug' => $slug,
            'questions_slug' => implode(',', $pins),
            'done_count' => 0,
            'result_type' => 0
        ]);

        return $this->resOK('pending');
    }

    /**
     * 获取试题
     */
    public function list(Request $request)
    {
        $user = $request->user();
        $slug = $request->get('slug');

        $sheet = QuestionSheet
            ::where('user_slug', $user->slug)
            ->where('tag_slug', $slug)
            ->first();

        if (is_null($sheet))
        {
            return $this->resErrNotFound('请重新开始答题');
        }

        $pins = explode(',', $sheet->questions_slug);
        if (empty($pins))
        {
            $sheet->delete();

            return $this->resErrNotFound('请重新开始答题');
        }

        $pinRepository = new PinRepository();
        $tagRepository = new TagRepository();

        $answers = PinAnswer
            ::where('user_slug', $user->slug)
            ->whereIn('pin_slug', $pins)
            ->pluck('selected_uuid', 'pin_slug')
            ->toArray();

        foreach ($answers as $key => $val)
        {
            $answers[$key] = [
                'selected_id' => json_decode($val, true)[0]
            ];
        }

        $result = $pinRepository->list($pins);

        return $this->resOK([
            'extra' => [
                'tag' => $tagRepository->item($slug),
                'answers' => $answers
            ],
            'result' => $result,
            'no_more' => true,
            'total' => count($result)
        ]);
    }

    /**
     * 交卷获得最终结果
     */
    public function submit(Request $request)
    {
        $user = $request->user();
        $slug = $request->get('slug');

        $sheet = QuestionSheet
            ::where('user_slug', $user->slug)
            ->where('tag_slug', $slug)
            ->first();

        if (is_null($sheet))
        {
            return $this->resErrNotFound('没有找到试卷');
        }

        $pins = explode(',', $sheet->questions_slug);
        if (empty($pins))
        {
            $sheet->delete();

            return $this->resErrNotFound('请重新开始答题');
        }

        $tag = Tag
            ::where('slug', $slug)
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound('没有找到对应的分区');
        }

        $rule = QuestionRule
            ::where('tag_slug', $slug)
            ->first();

        if (is_null($rule))
        {
            return $this->resErrNotFound('没有找到答题规则');
        }

        $rightCount = PinAnswer
            ::whereIn('pin_slug', $pins)
            ->where('user_slug', $user->slug)
            ->where('is_right', 1)
            ->count();

        if (!$rightCount || ($rightCount / count($pins) * 100 < $rule->right_rate))
        {
            $sheet->update([
                'result_type' => 2
            ]);

            $sheet->delete();

            PinAnswer
                ::whereIn('pin_slug', $pins)
                ->where('user_slug', $user->slug)
                ->delete();

            return $this->resOK('failed');
        }

        $sheet->update([
            'result_type' => 1
        ]);

        event(new \App\Events\User\JoinZone($user, $tag));

        return $this->resOK('pass');
    }

    /**
     * 查看这道题当前用户是怎么选的
     */
    public function result(Request $request)
    {
        $pinSlug = $request->get('slug');
        $user = $request->user();

        $hashStr = PinAnswer
            ::where('pin_slug', $pinSlug)
            ->where('user_slug', $user->slug)
            ->pluck('selected_uuid')
            ->first();

        if ($hashStr)
        {
            return $this->resOK(json_decode($hashStr, true));
        }

        return $this->resOK([]);
    }
}
