<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\BangumiRepository;
use App\Http\Repositories\QuestionRepository;
use App\Models\Bangumi;
use App\Models\BangumiQuestionAnswer;
use App\Models\BangumiQuestion;
use App\Models\BangumiQuestionSheet;
use App\Models\BangumiQuestionRule;
use App\Services\Trial\WordsFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JoinController extends Controller
{
    public function getJoinRule(Request $request)
    {
        $slug = $request->get('slug');
        if (!$slug)
        {
            return $this->resErrBad();
        }

        $bangumiRepository = new BangumiRepository();
        $rule = $bangumiRepository->rule($slug);
        if (!$rule)
        {
            $rule = [
                'right_rate' => 80,
                'question_count' => 30
            ];
        }

        return $this->resOK($rule);
    }

    public function updateJoinRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bangumi_slug' => 'required|string',
            'question_count' => 'required|integer|max:100|min:1',
            'right_rate' => 'required|integer|max:100|min:50',
            'qa_minutes' => 'required|integer|max:120|min:5',
            'rule_type' => 'required|integer',
            'is_open' => 'required|boolean',
            'result_type' => 'required|integer',
        ]);
        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $slug = $request->get('bangumi_slug');
        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($slug);
        if (!$bangumi)
        {
            return $this->resErrNotFound();
        }

        $user = $request->user();
        if ($user->cant('change_tag_rule'))
        {
            return $this->resErrRole();
        }

        BangumiQuestionRule
            ::where('bangumi_slug', $slug)
            ->update([
                'question_count' => $request->get('question_counts'),
                'right_rate' => $request->get('right_rate'),
                'qa_minutes' => $request->get('qa_minutes'),
                'rule_type' => $request->get('rule_type'),
                'result_type' => $request->get('result_type'),
                'is_open' => $request->get('is_open')
            ]);

        $bangumiRepository->rule($slug, true);

        return $this->resNoContent();
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bangumi_slug' => 'required|string',
            'title' => 'required|string|max:50',
            'answers' => 'required|array',
            'right_index' => 'required|integer|min:0|max:3'
        ]);
        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $slug = $request->get('bangumi_slug');
        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($slug);
        if (!$bangumi)
        {
            return $this->resErrNotFound();
        }

        $user = $request->user();
        if ($user->cant('create_qa'))
        {
            return $this->resErrRole();
        }

        $answers = [];
        $title = $request->get('title');
        $content = $request->get('answers');
        $rightIndex = $request->get('right_index');
        $wordsFilter = new WordsFilter();
        if ($wordsFilter->count($title))
        {
            return $this->resErrBad('触发敏感词');
        }

        $right_id = '';
        foreach ($content as $index => $row)
        {
            if ($wordsFilter->count($row))
            {
                return $this->resErrBad('触发敏感词');
            }

            $id = hash('md5', $row);
            $answers[$id] = $row;

            if ($rightIndex === $index)
            {
                $right_id = $id;
            }
        }

        $qa = BangumiQuestion
            ::create([
                'bangumi_slug' => $slug,
                'user_slug' => $user->slug,
                'title' => $title,
                'answers' => json_encode($answers),
                'right_id' => $right_id
            ]);

        return $this->resCreated($qa);
    }

    public function delete(Request $request)
    {
        $user = $request->user();
        if ($user->cant('delete_qa'))
        {
            return $this->resErrRole();
        }

        $questionId = $request->get('id');
        BangumiQuestion
            ::where('id', $questionId)
            ->update([
                'status' => 2
            ]);

        return $this->resNoContent();
    }

    public function recommend(Request $request)
    {
        $user = $request->user();
        if ($user->cant('trial_qa'))
        {
            return $this->resErrRole();
        }

        $id = $request->get('id');
        $question = BangumiQuestion
            ::where('id', $id)
            ->first();
        if (!$question)
        {
            return $this->resErrNotFound();
        }
        $question->update([
            'status' => 1
        ]);

        return $this->resNoContent();
    }

    public function flow(Request $request)
    {
        $user = $request->user();
        if ($user->cant('visit_qa'))
        {
            return $this->resErrRole();
        }

        $page = $request->get('page') ?: 1;
        $take = $request->get('count') ?: 10;
        $bangumiSlug = $request->get('bangumi_slug') ?: '';
        $userSlug = $request->get('user_slug') ?: '';
        $status = $request->get('status') ?: '';
        $sort = $request->get('sort') ?: 'newest';

        $ids = BangumiQuestion
            ::when($bangumiSlug, function ($query) use ($bangumiSlug)
            {
                return $query->where('bangumi_slug', $bangumiSlug);
            })
            ->when($userSlug, function ($query) use ($userSlug)
            {
                return $query->where('user_slug', $userSlug);
            })
            ->when($status, function ($query) use ($status)
            {
                return $query->where('status', $status);
            })
            ->when($sort === 'newest', function ($query)
            {
                return $query->orderBy('id', 'ASC');
            }, function ($query) use ($sort)
            {
                return $query
                    ->orderBy('like_count', $sort === 'like_desc' ? 'DESC' : 'ASC')
                    ->orderBy('id', 'ASC');
            })
            ->take($take)
            ->skip(($page - 1) * $take)
            ->pluck('id')
            ->toArray();

        $questionRepository = new QuestionRepository();
        $result = $questionRepository->list($ids);

        $answers = BangumiQuestion
            ::whereIn('id', $ids)
            ->pluck('right_id', 'id')
            ->toArray();

        return $this->resOK([
            'extra' => [
                'answers' => $answers
            ],
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
        $retry = $request->get('retry') ?: false;

        $sheet = BangumiQuestionSheet
            ::where('user_slug', $user->slug)
            ->where('bangumi_slug', $slug)
            ->where('result_type', '<', 2)
            ->first();

        if ($sheet)
        {
            if ($retry)
            {
                $sheet->delete();
            }
            else
            {
                return $this->resOK($sheet->result_type == 0 ? 'pending' : 'resolve');
            }
        }

        $rule = BangumiQuestionRule
            ::where('bangumi_slug', $slug)
            ->first();

        $count = 30;
        if ($rule)
        {
            $count = $rule->question_count;
            if ($rule->rule_type == 1)
            {
                return $this->resErrRole('该分区只能邀请加入');
            }
        }

        $ids = BangumiQuestion
            ::where('bangumi_slug', $slug)
            ->where('status', 1)
            ->take($count)
            ->pluck('id')
            ->toArray();

        if (count($ids) < $count)
        {
            return $this->resOK('no_question');
        }

        BangumiQuestionSheet
            ::create([
                'user_slug' => $user->slug,
                'bangumi_slug' => $slug,
                'question_ids' => implode(',', $ids)
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

        $sheet = BangumiQuestionSheet
            ::where('user_slug', $user->slug)
            ->where('bangumi_slug', $slug)
            ->where('result_type', 0)
            ->first();

        if (is_null($sheet))
        {
            return $this->resErrNotFound('请重新开始答题');
        }

        $ids = explode(',', $sheet->question_ids);
        if (empty($ids))
        {
            $sheet->delete();

            return $this->resErrNotFound('请重新开始答题');
        }

        $questionRepository = new QuestionRepository();
        $answers = BangumiQuestionAnswer
            ::where('user_slug', $user->slug)
            ->whereIn('question_id', $ids)
            ->pluck('answer_id', 'question_id')
            ->toArray();

        $result = $questionRepository->list($ids);

        return $this->resOK([
            'extra' => [
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

        $sheet = BangumiQuestionSheet
            ::where('user_slug', $user->slug)
            ->where('bangumi_slug', $slug)
            ->where('result_type', 0)
            ->first();

        if (is_null($sheet))
        {
            return $this->resErrNotFound('没有找到试卷');
        }

        $ids = explode(',', $sheet->questions_slug);
        if (empty($ids))
        {
            $sheet->delete();

            return $this->resErrNotFound('请重新开始答题');
        }

        $bangumi = Bangumi
            ::where('slug', $slug)
            ->first();
        if (is_null($bangumi))
        {
            return $this->resErrNotFound('没有找到对应的番剧');
        }

        $rule = BangumiQuestionRule
            ::where('bangumi_slug', $slug)
            ->first();

        $rightRate = 80;
        if (is_null($rule))
        {
            $rightRate = $rule->right_rate;
        }

        $rightCount = BangumiQuestionAnswer
            ::whereIn('question_id', $ids)
            ->where('user_slug', $user->slug)
            ->where('is_right', 1)
            ->count();

        if (!$rightCount || ($rightCount / count($pins) * 100 < $rightRate))
        {
            $sheet->update([
                'result_type' => 2
            ]);

            return $this->resOK('failed');
        }

        $sheet->update([
            'result_type' => 1
        ]);

        if (!$bangumi->isLikedBy($user))
        {
            event(new \App\Events\Bangumi\Pass($user, $bangumi));
        }

        return $this->resOK('pass');
    }

    /**
     * 查看这道题当前用户是怎么选的
     */
    public function result(Request $request)
    {
        $id = $request->get('id');
        $user = $request->user();

        $answerId = BangumiQuestionAnswer
            ::where('question_id', $id)
            ->where('user_slug', $user->slug)
            ->pluck('answer_id')
            ->first();

        if ($answerId)
        {
            return $this->resOK($answerId);
        }

        return $this->resOK();
    }
}
