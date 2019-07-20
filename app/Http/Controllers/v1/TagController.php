<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\TagPatchCounter;
use App\Http\Repositories\TagRepository;
use App\Http\Transformers\Tag\TagItemResource;
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
            'rule_type' => 'required|integer'
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

        QuestionRule
            ::where('tag_slug', $request->get('tag_slug'))
            ->update([
                'question_count' => $request->get('question_count'),
                'right_rate' => $request->get('right_rate'),
                'qa_minutes' => $request->get('qa_minutes'),
                'rule_type' => $request->get('rule_type'),
            ]);

        $tagRepository = new TagRepository();
        $tagRepository->rule($request->get('tag_slug'), true);

        return $this->resNoContent();
    }

    public function createQA(Request $request)
    {
        return $this->resErrRole();
    }

    /**
     * 所有的子标签迁移到目标标签
     * 该标签下的内容和关注关系迁移到目标标签
     * // TODO：events
     */
    public function combine(Request $request)
    {
        $user = $request->user();
        if ($user->cant('combine_tag'))
        {
            return $this->resErrRole();
        }

        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'target_slug' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $slug = $request->get('slug');
        $tag = Tag
            ::where('slug', $request->get('slug'))
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound();
        }

        $targetSlug = $request->get('target_slug');
        $target = Tag
            ::where('slug', $targetSlug)
            ->first();

        if (is_null($target))
        {
            return $this->resErrNotFound();
        }

        Tag
            ::where('parent_slug', $slug)
            ->update([
                'parent_slug' => $targetSlug,
                'deep' => $target + 1
            ]);

        // TODO cache

        return $this->resNoContent();
    }

    /**
     * 将近义词 tag 重定向过去
     * // TODO：events
     */
    public function relink(Request $request)
    {
        $user = $request->user();
        if ($user->cant('relink_tag'))
        {
            return $this->resErrRole();
        }

        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'target_slug' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $tag = Tag
            ::where('slug', $request->get('slug'))
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound();
        }

        $target = Tag
            ::where('slug', $request->get('target_slug'))
            ->first();

        if (is_null($target))
        {
            return $this->resErrNotFound();
        }

        $tag->update([
            'parent_slug' => $request->get('target_slug'),
            'deep' => $target->deep + 1
        ]);
        // TODO cache

        return $this->resNoContent();
    }
}
