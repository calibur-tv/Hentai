<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use App\Models\Pin;
use App\Models\Tag;
use App\Services\Spider\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PinController extends Controller
{
    /**
     * 查看帖子（最好直接返回缓存，支持带 password 参数）
     */
    public function show_info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'key' => 'nullable|string',
            'ts' => 'nullable|integer'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($request->get('slug'));

        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        if ($pin->deleted_at != null)
        {
            if ($pin->trial_type != 0)
            {
                return $this->resErrLocked();
            }

            return $this->resErrNotFound();
        }

        if ($pin->visit_type != 0)
        {
            $key = $request->get('key');
            $ts = $request->get('ts');
            if (!$key || !$ts)
            {
                return $this->resErrRole();
            }

            if ($key !== md5(slug2id($pin->slug), $ts))
            {
                return $this->resErrRole('密码不正确');
            }

            if (abs(time() - $ts) > 3000)
            {
                return $this->resErrRole('密码已过期');
            }
        }

        return $this->resOK($pin);
    }

    /**
     * 返回无法缓存的数据（special的）
     */
    public function show_meta(Request $request)
    {
        return $this->resOK('1');
    }

    /**
     * 创建帖子
     */
    public function createDaily(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('站长'))
        {
            return $this->resErrRole();
        }

        $validator = Validator::make($request->all(), [
            'images' => 'array|present',
            'title' => 'present|string|max:30',
            'content' => 'required|string|max:10000',
            'area' => 'required|string',
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $area = $request->get('area');
        $tag = Tag
            ::where('slug', $area)
            ->first();

        if (is_null($tag))
        {
            return $this->resErrNotFound();
        }

        if (!$user->hasBookmarked($tag))
        {
            return $this->resErrRole();
        }

        $images = $request->get('images');
        $formatImages = [];
        foreach ($images as $img)
        {
            $validator = Validator::make($img, [
                'url' => 'required|string',
                'width' => 'required|integer',
                'height' => 'required|integer',
                'size' => 'required|integer',
                'mime' => 'required|string',
            ]);

            if ($validator->fails())
            {
                return $this->resErrParams($validator);
            }

            $formatImages[] = [
                'type' => 'image',
                'data' => [
                    'file' => $img,
                    'caption' => '',
                    'stretched' => false,
                    'withBackground' => false,
                    'withBorder' => false
                ]
            ];
        }

        $content = array_merge([[
            'type' => 'paragraph',
            'data' => [
                'text' => $request->get('content')
            ]
        ]], $formatImages);

        $pin = Pin::createPin([
            'title' => $request->get('title'),
            'tag' => $tag,
            'content' => $content,
            'image_count' => count($formatImages)
        ], $user);

        if (is_null($pin))
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        return $this->resCreated($pin);
    }

    public function update(Request $request)
    {

    }

    /**
     * 给帖子设置标签
     */
    public function toggle_tag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin_slug' => 'required|string',
            'tag_slug' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($request->get('pin_slug'));
        if (is_null($pin))
        {
            return $this->resErrNotFound('不存在的内容');
        }

        $tagRepository = new TagRepository();
        $tag = $tagRepository->category_tags($request->get('tag_slug'));
        if (is_null($tag))
        {
            return $this->resErrNotFound('不存在的标签');
        }

        $user = $request->user();
        $record = PinTag
            ::where('pin_id', $pin->id)
            ->where('tag_id', $tag->id)
            ->where('user_id', $user->id)
            ->first();

        if (is_null($record))
        {
            PinTag::create([
                'pin_id' => $pin->id,
                'tag_id' => $tag->id,
                'user_id' => $user->id
            ]);

            Pin
                ::where('id', $pin->id)
                ->increment('tag_count');
        }
        else
        {
            $record->delete();

            Pin
                ::where('id', $pin->id)
                ->increment('tag_count', -1);
        }
        // TODO cache

        return $this->resNoContent();
    }

    public function fetchSiteMeta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $url = $request->get('url');

        $query = new Query();
        $result = $query->fetchMeta(urldecode($url));

        return response([
            'success' => 1,
            'meta' => $result
        ], 200);
    }

    /**
     * 删除帖子（作者或管理员）
     */
    public function destroy(Request $request)
    {

    }

    /**
     * 举报入口，修改 trial_type
     */
    public function report(Request $request)
    {

    }

    /**
     * 审核列表
     */
    public function trials(Request $request)
    {

    }

    /**
     * 审核通过
     */
    public function resolve(Request $request)
    {

    }

    /**
     * 审核不通过
     */
    public function reject(Request $request)
    {

    }
}
