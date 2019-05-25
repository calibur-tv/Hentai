<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositorys\v1\TagRepository;
use App\Http\Transformers\Tag\TagItemResource;
use App\Models\Tag;
use App\Services\Trial\WordsFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

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
     * 创建 tag（走先审后发流程）
     */
    public function create(Request $request)
    {
        $user = $request->user();
        if ($user->cant('create_tag'))
        {
            return $this->resErrRole();
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:32',
            'parent_slug' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $name = Purifier::clean($request->get('name'));
        $parentSlug = $request->get('parent_slug');

        $parentTag = Tag
            ::where('slug', $parentSlug)
            ->first();

        if (is_null($parentTag))
        {
            return $this->resErrBad();
        }

        $wordsFilter = new WordsFilter();
        if ($wordsFilter->count($name))
        {
            return $this->resErrBad();
        }

        $tag = Tag::create([
            'name' => $name,
            'parent_slug' => $parentSlug,
            'deep' => $parentTag->deep + 1,
            'creator_id' => 1 // TODO
        ]);

        $tag->extra()->create([
            'text' => json_encode([
                'alias' => $name,
                'intro' => ''
            ])
        ]);

        $tag->update([
            'slug' => $this->id2slug($tag->id)
        ]);

        // TODO 操作缓存

        return $this->resOK(new TagItemResource($tag));
    }

    /**
     * 更新 tag（头像，拼写错误，仅支持后台操作）
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->cant('update_tag'))
        {
            return $this->resErrRole();
        }

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

        $tag = Tag
            ::where('slug', $request->get('slug'))
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound();
        }

        $tag->update([
            'avatar' => $this->convertImagePath($request->get('avatar')),
            'name' => Purifier::clean($request->get('name'))
        ]);

        $tag->updateExtra([
            'intro' => Purifier::clean($request->get('intro')),
            'alias' => Purifier::clean($request->get('alias'))
        ]);

        // TODO 敏感词检测
        // TODO 操作缓存

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

        $trashSlug = 'fa0';
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

        $tag->delete();

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
                'parent_slug' => $targetSlug
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
            'parent_slug' => $request->get('target_slug')
        ]);
        // TODO cache

        return $this->resNoContent();
    }
}
