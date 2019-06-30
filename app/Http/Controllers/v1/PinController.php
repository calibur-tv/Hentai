<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\PinPatchCounter;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use App\Models\Pin;
use App\Services\Spider\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PinController extends Controller
{
    public function show(Request $request)
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

    public function patch(Request $request)
    {
        $slug = $request->get('slug');
        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($slug);

        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        $pinPatchCounter = new PinPatchCounter();
        $patch = $pinPatchCounter->all($slug);

        $patch['trial_type'] = $pin->trial_type;
        $patch['visit_type'] = $pin->visit_type;
        $patch['comment_type'] = $pin->comment_type;
        $patch['recommended_at'] = $pin->recommended_at;
        $patch['last_top_at'] = $pin->last_top_at;
        $patch['deleted_at'] = $pin->deleted_at;

        return $this->resOK($patch);
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

        $pin = Pin::createPin(
            $request->get('content'),
            1,
            $request->get('publish') ? 0 : 1,
            $user->slug
        );

        if (is_null($pin))
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        event(new \App\Events\Pin\Create($pin, $area, $notebook));

        if ($pin->visit_type != 0)
        {
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
            return $this->resErrRole('不能修改的文章');
        }

        $pin = Pin::updatePin(
            $slug,
            $request->get('content'),
            $request->get('publish') ? 0 : $pin->visit_type
        );

        if (is_null($pin))
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        event(new \App\Events\Pin\Update($pin));

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
