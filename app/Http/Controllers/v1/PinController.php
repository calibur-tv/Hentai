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

            if ($key !== md5(config('app.md5') . $pin->slug . $ts))
            {
                return $this->resErrRole('密码不正确');
            }

            if (abs(time() - $ts) > 300)
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

        $tagRepository = new TagRepository();
        $tag = $tagRepository->getMarkedTag($request->get('area'), $user);

        if (null === $tag)
        {
            return $this->resErrNotFound();
        }

        if (false === $tag)
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

        $content = array_merge([
            [
                'type' => 'title',
                'data' => [
                    'text' => $request->get('title')
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => $request->get('content')
                ]
            ]
        ], $formatImages);

        $pin = Pin::createPin([
            'tag' => $tag,
            'content' => $content,
            'image_count' => count($formatImages),
            'content_type' => 0
        ], $user);

        if (is_null($pin))
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        return $this->resCreated($pin);
    }

    public function createStory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|array',
            'area' => 'required|string',
            'notebook' => 'required|string',
            'publish' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $tagRepository = new TagRepository();
        $user = $request->user();

        $area = $tagRepository->getMarkedTag($request->get('area'), $user);
        if (null === $area)
        {
            return $this->resErrNotFound('不能存在的分区');
        }
        if (false === $area)
        {
            return $this->resErrRole('未解锁的分区');
        }

        $notebook = $tagRepository->getMarkedTag($request->get('notebook'), $user);
        if (null === $notebook)
        {
            return $this->resErrNotFound('不能存在的专栏');
        }
        if (false === $notebook)
        {
            return $this->resErrRole('不属于自己的专栏');
        }
        if ($notebook->parent_slug !== config('app.tag.notebook'))
        {
            return $this->resErrBad('非法的专栏');
        }

        $pin = Pin::createPin([
            'area' => $area,
            'notebook' => $notebook,
            'content' => $request->get('content'),
            'content_type' => 1,
            'visit_type' => $request->get('publish') ? 0 : 1
        ], $user);

        if (is_null($pin))
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        if ($pin->visit_type != 0)
        {
            $pinRepository = new PinRepository();
            $pinRepository->drafts($user->slug, 0, 0, true);
            $ts = time();
            return $this->resCreated($pin->slug . '?key=' . (md5(config('app.md5') . $pin->slug . $ts)) . '&ts=' . $ts);
        }

        return $this->resCreated($pin->slug);
    }

    public function updateStory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'content' => 'required|array',
            'area' => 'required|string',
            'notebook' => 'required|string',
            'publish' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $user = $request->user();
        $slug = $request->get('slug');
        $pinRepository = new PinRepository();

        $pin = $pinRepository->item($slug);
        if (is_null($pin))
        {
            return $this->resErrNotFound('不存在的文章');
        }

        if ($pin->author->slug != $user->slug)
        {
            return $this->resErrRole('不是自己的文章');
        }

        $tagRepository = new TagRepository();

        $tag = $tagRepository->getMarkedTag($request->get('area'), $user);
        if (null === $tag)
        {
            return $this->resErrNotFound('不存在的分区');
        }
        if (false === $tag)
        {
            return $this->resErrRole('未解锁的分区');
        }

        $notebook = $tagRepository->getMarkedTag($request->get('notebook'), $user);
        if (null === $notebook)
        {
            return $this->resErrNotFound('不能存在的专栏');
        }
        if (false === $notebook)
        {
            return $this->resErrRole('不属于自己的专栏');
        }
        if ($notebook->parent_slug !== config('app.tag.notebook'))
        {
            return $this->resErrBad('非法的专栏');
        }

        $pin = Pin::updatePin([
            'slug' => $slug,
            'tag' => $tag,
            'notebook' => $notebook,
            'content' => $request->get('content'),
            'visit_type' => $request->get('publish') ? 0 : $pin->visit_type
        ], $user);

        if (is_null($pin))
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        $pinRepository->item($slug, true);
        $pinRepository->drafts($user->slug, 0, 0, true);

        if ($pin->visit_type != 0)
        {
            $ts = time();
            return $this->resOK($pin->slug . '?key=' . (md5(config('app.md5') . $pin->slug . $ts)) . '&ts=' . $ts);
        }

        return $this->resOK($pin->slug);
    }

    public function deletePin(Request $request)
    {
        $user = $request->user();
        $slug = $request->get('slug');
        $pinRepository = new PinRepository();

        $pin = $pinRepository->item($slug);
        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        if ($pin->author->slug != $user->slug)
        {
            return $this->resErrRole();
        }

        Pin::deletePin($slug, $user, 2);
        $pinRepository->item($slug, true);

        return $this->resNoContent();
    }

    public function getEditableContent(Request $request)
    {
        $slug = $request->get('slug');
        $user = $request->user();

        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($slug);
        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        if ($pin->author->slug != $user->slug)
        {
            return $this->resErrRole();
        }

        if ($pin->deleted_at != null)
        {
            return $this->resErrNotFound();
        }

        return $this->resOK($pin);
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

    public function userDrafts(Request $request)
    {
        $user = $request->user();
        $page = $request->get('page') ?: 1;
        $take = $request->get('count') ?: 10;

        $pinRepository = new PinRepository();

        $ids = $pinRepository->drafts($user->slug, $page - 1, $take);
        if ($ids['total'] === 0)
        {
            return $this->resOK($ids);
        }

        $pins = $pinRepository->list($ids['result']);

        $secret = [];
        $ts = time();
        $salt = config('app.md5');

        foreach ($pins as $pin)
        {
            $secret[] = $pin->slug . '?key=' . (md5($salt . $pin->slug . $ts)) . '&ts=' . $ts;
        }

        $ids['result'] = $pins;
        $ids['extra'] = $secret;

        return $this->resOK($ids);
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
