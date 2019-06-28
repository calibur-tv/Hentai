<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\TagRepository;
use App\Http\Transformers\Tag\TagItemResource;
use App\Models\Tag;
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
     * 设置班长
     */
    public function toggle_master(Request $request)
    {

    }

    /**
     * 创建 tag（走先审后发流程）
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

        $tagRepository = new TagRepository();
        $parentTag = $tagRepository->item($parentSlug);

        if (is_null($parentTag))
        {
            return $this->resErrBad();
        }

        $wordsFilter = new WordsFilter();
        if ($wordsFilter->count($name))
        {
            return $this->resErrBad();
        }

        $tag = Tag::createTag(
            [
                'name' => $name,
                'parent_slug' => $parentSlug,
                'creator_slug' => $user->slug,
                'deep' => $parentTag->deep + 1
            ],
            [
                'alias' => $name,
                'intro' => ''
            ]
        );

        // TODO 操作缓存
        if ($isNotebook)
        {
            $user->bookmark($tag, Tag::class);
            $tagRepository->bookmarks($user->slug, true);
        }

        return $this->resOK(new TagItemResource($tag));
    }

    /**
     * 更新 tag（头像，拼写错误，仅支持后台操作）
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

        $tag->updateTag(
            [
                'avatar' => $request->get('avatar'),
                'name' => $request->get('name')
            ],
            [
                'intro' => $request->get('intro'),
                'alias' => $request->get('alias')
            ]
        );

        // TODO 敏感词检测
        $tagRepository = new TagRepository();
        $tagRepository->item($slug, true);
        $tagRepository->relation_item($slug, true);
        $tagRepository->bookmarks($slug, true);

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

        $trashSlug = config('app.tag.trash');
        $slug = $request->get('slug');

        if ($slug === $trashSlug)
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

        $tag->deleteTag();

        Tag
            ::where('parent_slug', $slug)
            ->update([
                'parent_slug' => $trashSlug // 回收站
            ]);

        // TODO cache

        return $this->resNoContent();
    }

    /**
     * 所有的子标签迁移到目标标签
     * 该标签下的内容和关注关系迁移到目标标签
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
