<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\TagPatchCounter;
use App\Http\Repositories\TagRepository;
use App\Http\Transformers\Tag\TagItemResource;
use App\Models\Pin;
use App\Models\QuestionRule;
use App\Models\Tag;
use App\Services\Trial\ImageFilter;
use App\Services\Trial\WordsFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    /**
     * 根据 parent_slug 获取到 children
     * parent_slug 支持 string 和 array？
     */
    public function show(Request $request)
    {
        $slug = $request->get('slug');

        $tagRepository = new TagRepository();
        $data = $tagRepository->relation_item($slug);
        if (is_null($data))
        {
            return $this->resErrNotFound();
        }

        return $this->resOK($data);
    }

    public function patch(Request $request)
    {
        $slug = $request->get('slug');

        $tagRepository = new TagRepository();
        $data = $tagRepository->item($slug);
        if (is_null($data))
        {
            return $this->resErrNotFound();
        }

        $tagPatchCounter = new TagPatchCounter();
        $patch = $tagPatchCounter->all($slug);
        $user = $request->user();

        if (!$user)
        {
            return $this->resOK($patch);
        }

        $tagId = slug2id($slug);
        $patch['is_followed'] = $user->isFollowing($tagId, Tag::class);
        $patch['is_marked'] = $user->hasBookmarked($tagId, Tag::class);

        return $this->resOK($patch);
    }

    public function batchPatch(Request $request)
    {
        $list = $request->get('slug') ? explode(',', $request->get('slug')) : [];
        $tagPatchCounter = new TagPatchCounter();

        $result = [];
        foreach ($list as $slug)
        {
            $result[$slug] = $tagPatchCounter->all($slug);
        }

        return $this->resOK($result);
    }

    /**
     * 获取用户的收藏版区
     */
    public function bookmarks(Request $request)
    {
        $slug = $request->get('slug');

        $tagRepository = new TagRepository();
        $result = $tagRepository->bookmarks($slug);

        if (is_null($result))
        {
            return $this->resErrNotFound();
        }

        return $this->resOK($result);
    }

    /**
     * 创建 tag
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:32',
            'parent_slug' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $name = $request->get('name');
        $user = $request->user();
        $parentSlug = $request->get('parent_slug');
        $isNotebook = $parentSlug === config('app.tag.notebook');
        if (!$isNotebook && $user->cant('create_tag'))
        {
            return $this->resErrRole();
        }

        $parent = Tag
            ::where('slug', $parentSlug)
            ->first();

        if (is_null($parent))
        {
            return $this->resErrBad();
        }

        $wordsFilter = new WordsFilter();
        if ($wordsFilter->count($name))
        {
            return $this->resErrBad();
        }

        $tag = Tag::createTag($name, $user, $parent);

        return $this->resOK(new TagItemResource($tag));
    }

    /**
     * 更新 tag
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:32',
            'slug' => 'required|string',
            'avatar' => 'required|string',
            'intro' => 'required|string|max:233',
            'alias' => 'required|string|max:100',
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $user = $request->user();
        $slug = $request->get('slug');

        $tag = Tag
            ::where('slug', $slug)
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound();
        }

        if ($tag->creator_slug !== $user->slug)
        {
            return $this->resErrRole();
        }

        $name = $request->get('name');
        $intro = $request->get('intro');
        $alias = $request->get('alias');

        $wordsFilter = new WordsFilter();
        if ($wordsFilter->count($name . $intro . $alias))
        {
            return $this->resErrBad('请修改文字');
        }

        $image = $request->get('avatar');
        $imageFilter = new ImageFilter();
        $result = $imageFilter->check($image);
        if ($result['delete'] || $result['review'])
        {
            return $this->resErrBad('请更换图片');
        }

        $tag->updateTag([
            'avatar' => trimImage($image),
            'name' => $name,
            'intro' => $intro,
            'alias' => $alias
        ], $user);

        return $this->resNoContent();
    }

    /**
     * 删除 tag，子标签移到回收站
     */
    public function delete(Request $request)
    {
        $user = $request->user();
        if ($user->cant('delete_tag'))
        {
            return $this->resErrRole();
        }

        $validator = Validator::make($request->all(), [
            'slug' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $slug = $request->get('slug');

        if ($slug === config('app.tag.trash'))
        {
            return $this->resErrRole();
        }

        $tag = Tag
            ::where('slug', $slug)
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound();
        }

        if ($tag->parent_slug === config('app.tag.calibur'))
        {
            return $this->resNoContent();
        }

        $tag->deleteTag($user);

        return $this->resNoContent();
    }

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
            'question_count' => 'required|integer|max:100|min:30',
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

    /**
     * 为题库添加题目
     */
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
        $answers = $request->get('answers');
        $items = [];
        $ids = [];
        foreach ($answers as $i => $ans)
        {
            $id = str_rand();
            $items[] = [
                'id' => $id,
                'text' => $ans
            ];
            $ids[] = $id;
        }
        $content[] = [
            'type' => 'vote',
            'data' => [
                'items' => $items,
                'right_id' => [$ids[$request->get('right_index')]]
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

    /**
     * 更新某个题目
     */
    public function updateQA(Request $request)
    {

    }

    /**
     * 删除某个题目
     */
    public function deleteQA(Request $request)
    {

    }

    /**
     * 用户开始答题，给他发卷
     */
    public function beginQA(Request $request)
    {

    }

    /**
     * 检查某道题是否答对
     */
    public function checkQA(Request $request)
    {

    }

    /**
     * 获取题目列表，不包括选项
     */
    public function Questions(Request $request)
    {

    }

    /**
     * 获取题目，包括问题和选项
     */
    public function showQA(Request $request)
    {

    }
}
