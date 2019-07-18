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
            $errMessage = $pinRepository->decrypt($request);
            if ($errMessage)
            {
                return $this->resErrRole($errMessage);
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

        $user = $request->user();
        if ($user && $user->slug !== $pin->author->slug)
        {
            $pinId = slug2id($slug);
            $patch['visit_count']++;
            $patch['down_vote_status'] = false;
            $patch['up_vote_status'] = $user->hasUpvoted($pinId, Pin::class);
            $patch['mark_status'] = $user->hasBookmarked($pinId, Pin::class);
            $patch['reward_status'] = $user->hasFavorited($pinId, Pin::class);
            $pinPatchCounter->add($slug, 'visit_count');
        }
        else
        {
            $patch['up_vote_status'] = false;
            $patch['down_vote_status'] = false;
            $patch['mark_status'] = false;
            $patch['reward_status'] = false;
        }

        return $this->resOK($patch);
    }

    public function batchPatch(Request $request)
    {
        $list = $request->get('slug') ? explode(',', $request->get('slug')) : [];
        $pinPatchCounter = new PinPatchCounter();

        $result = [];
        foreach ($list as $slug)
        {
            $result[$slug] = $pinPatchCounter->all($slug);
        }

        return $this->resOK($result);
    }

    public function createStory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|array',
            'area' => 'present|string',
            'topic' => 'required|string',
            'notebook' => 'required|string',
            'publish' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $tagRepository = new TagRepository();
        $user = $request->user();

        $area = $request->get('area');
        if ($area)
        {
            $area = $tagRepository->checkTagIsMarked($request->get('area'), $user);
            if (null === $area)
            {
                return $this->resErrNotFound('不存在的分区');
            }
            if (false === $area)
            {
                return $this->resErrRole('未解锁的分区');
            }
        }

        $topic = $tagRepository->checkTagIsMarked($request->get('topic'), $user);
        if (null === $topic)
        {
            return $this->resErrNotFound('不存在的话题');
        }
        if (false === $topic)
        {
            return $this->resErrRole('未关注的话题');
        }

        $notebook = $tagRepository->checkTagIsMarked($request->get('notebook'), $user);
        if (null === $notebook)
        {
            return $this->resErrNotFound('不存在的专栏');
        }
        if (false === $notebook)
        {
            return $this->resErrRole('不属于自己的专栏');
        }
        if ($notebook->parent_slug !== config('app.tag.notebook'))
        {
            return $this->resErrBad('非法的专栏');
        }

        $tags = [
            $notebook->slug,
            $topic->slug,
            config('app.tag.topic')
        ];
        if ($area)
        {
            $tags[] = $area->slug;
            $tags[] = $area->parent_slug;
        }

        $pin = Pin::createPin(
            $request->get('content'),
            1,
            $request->get('publish') ? 0 : 1,
            $user,
            $tags
        );

        if (is_null($pin))
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        if ($pin->visit_type != 0)
        {
            $pinRepository = new PinRepository();
            return $this->resCreated($pinRepository->encrypt($pin->slug));
        }

        return $this->resCreated($pin->slug);
    }

    public function updateStory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'content' => 'required|array',
            'area' => 'present|string',
            'topic' => 'required|string',
            'notebook' => 'required|string',
            'publish' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $user = $request->user();
        $slug = $request->get('slug');

        $pin = Pin
            ::where('slug', $slug)
            ->first();

        if (is_null($pin))
        {
            return $this->resErrNotFound('不存在的文章');
        }

        if ($pin->user_slug != $user->slug)
        {
            return $this->resErrRole('不能修改别人的文章');
        }

        $tagRepository = new TagRepository();
        $user = $request->user();

        $area = $request->get('area');
        if ($area)
        {
            $area = $tagRepository->checkTagIsMarked($request->get('area'), $user);
            if (null === $area)
            {
                return $this->resErrNotFound('不存在的分区');
            }
            if (false === $area)
            {
                return $this->resErrRole('未解锁的分区');
            }
        }

        $topic = $tagRepository->checkTagIsMarked($request->get('topic'), $user);
        if (null === $topic)
        {
            return $this->resErrNotFound('不存在的话题');
        }
        if (false === $topic)
        {
            return $this->resErrRole('未关注的话题');
        }

        $notebook = $tagRepository->checkTagIsMarked($request->get('notebook'), $user);
        if (null === $notebook)
        {
            return $this->resErrNotFound('不存在的专栏');
        }
        if (false === $notebook)
        {
            return $this->resErrRole('不属于自己的专栏');
        }
        if ($notebook->parent_slug !== config('app.tag.notebook'))
        {
            return $this->resErrBad('非法的专栏');
        }

        $tags = [
            $notebook->slug,
            $topic->slug,
            config('app.tag.topic')
        ];
        if ($area)
        {
            $tags[] = $area->slug;
            $tags[] = $area->parent_slug;
        }

        $result = $pin->updatePin(
            $request->get('content'),
            $request->get('publish') ? 0 : $pin->visit_type,
            $user,
            $tags
        );

        if (!$result)
        {
            return $this->resErrBad('请勿发表敏感内容');
        }

        if ($pin->visit_type != 0)
        {
            $pinRepository = new PinRepository();
            return $this->resOK($pinRepository->encrypt($pin->slug));
        }

        return $this->resOK($pin->slug);
    }

    public function deletePin(Request $request)
    {
        $user = $request->user();
        $slug = $request->get('slug');

        $pin = Pin
            ::where('slug', $slug)
            ->first();

        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        if ($pin->user_slug != $user->slug && !$user->is_admin)
        {
            return $this->resErrRole();
        }

        $pin->deletePin($user);

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

    public function getMarkedTag(Request $request)
    {
        $userSlug = $request->get('user_slug');
        $pinSlug = $request->get('pin_slug');
        $pin = Pin
            ::where('slug', $pinSlug)
            ->first();

        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        $result = $pin
            ->tags()
            ->where('parent_slug', config('app.tag.notebook'))
            ->where('creator_slug', $userSlug)
            ->pluck('slug')
            ->first();

        return $this->resOK($result);
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
        foreach ($pins as $pin)
        {
            $secret[] = $pinRepository->encrypt($pin->slug);
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
