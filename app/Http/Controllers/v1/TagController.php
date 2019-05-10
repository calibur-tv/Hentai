<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositorys\v1\TagRepository;
use App\Http\Transformers\TagResource;
use App\Models\Pin;
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

        $wordsFilter = new WordsFilter();
        if ($wordsFilter->count($name))
        {
            return $this->resErrBad();
        }

        $tag = Tag::create([
            'name' => $name,
            'parent_slug' => $parentSlug,
            'creator_id' => 1 // TODO
        ]);

        $tag->update([
            'slug' => $this->id2slug($tag->id)
        ]);

        // TODO 操作缓存

        return $this->resOK(new TagResource($tag));
    }

    /**
     * 更新 tag（头像，拼写错误，仅支持后台操作）
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:32',
            'slug' => 'required|string',
            'avatar' => 'present|string'
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
            'name' => Purifier::clean($request->get('name')),
            'avatar' => $request->get('avatar')
        ]);

        // TODO 操作缓存

        return $this->resNoContent();
    }

    /**
     * 删除 tag，并且删除 PinTag 中的数据
     * TODO：子标签怎么办
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string'
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

        $pins = PinTag
            ::where('tag_id', $tag->id)
            ->groupBy('tag_id') // TODO 拿到 count
            ->pluck('pin_id')
            ->get()
            ->toArray();

        foreach ($pins as $item)
        {
            Pin
                ::where('id', $item['pin_id'])
                ->increment('tag_count', -$item['xxx']);

            // TODO cache
        }

        // TODO cache
        PinTag
            ::where('tag_id', $tag->id)
            ->delete();
        $tag->delete();

        return $this->resNoContent();
    }

    /**
     * 将重复的 tag 合并起来
     * 旧的删掉
     * 子标签归到"垃圾箱"
     */
    public function combine(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'parent_slug' => 'required|string'
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

        $parent = Tag
            ::where('slug', $request->get('parent_slug'))
            ->first();

        if (is_null($parent))
        {
            return $this->resErrNotFound();
        }

        $tag->update([
            'parent_slug' => $request->get('parent_slug')
        ]);

        // TODO cache

        return $this->resNoContent();
    }

    /**
     * 将近义词 tag 重定向过去
     */
    public function redirect(Request $request)
    {
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
            'redirect_slug' => $request->get('target_slug')
        ]);
        // TODO cache

        return $this->resNoContent();
    }
}
