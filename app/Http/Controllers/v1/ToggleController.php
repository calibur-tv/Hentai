<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\VirtualCoinService;
use App\Models\Comment;
use App\Models\Pin;
use App\Models\Tag;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ToggleController extends Controller
{
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_slug' => 'required|string',
            'target_type' => [
                'required',
                Rule::in(['user', 'pin', 'tag', 'comment']),
            ],
            'action_slug' => 'required|string',
            'action_type' => [
                'required',
                Rule::in(['user', 'tag']),
            ],
            'method_type' => [
                'required',
                Rule::in(['like', 'bookmark', 'follow', 'favorite', 'subscribe', 'up_vote', 'down_vote']),
            ],
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $targetSlug = $request->get('target_slug');
        $targetType = $request->get('target_type');
        $methodType = $request->get('method_type');
        $actionType = $request->get('action_type');
        $actionSlug = $request->get('action_slug');

        $targetClass = $this->convertClass($targetType);
        if (is_null($targetClass))
        {
            return $this->resErrBad();
        }

        $target = $this->convertModel($targetType, $targetSlug);
        if (is_null($target))
        {
            return $this->resErrNotFound();
        }

        $user = $request->user();
        if ($this->getCreatorSlug($target, $targetType) === $user->slug)
        {
            return $this->resErrRole('自己的内容');
        }

        if ($actionType !== 'user' || $user->slug !== $actionSlug)
        {
            $object = $this->convertModel($actionType, $actionSlug);
        }
        else
        {
            $object = $user;
        }

        if (is_null($object))
        {
            return $this->resErrBad();
        }

        $errorMessage = $this->preValidator($object, $target, $targetType, $methodType);
        if ($errorMessage)
        {
            return $this->resErrBad($errorMessage);
        }

        $result = $this->preToggleAction($object, $target, $targetType, $methodType, $targetSlug);
        if (!$result)
        {
            return $this->resErrServiceUnavailable();
        }

        $result = $this->toggleAction($object, $target, $targetClass, $methodType);
        if (null === $result)
        {
            return $this->resErrServiceUnavailable();
        }

        $result = gettype($result) === 'array' ? empty($result['detached']) : $result;
        $this->emitToggleEvent($object, $target, $targetType, $methodType, $result);

        return $this->resOK($result);
    }

    protected function preToggleAction($object, $target, $targetType, $methodType, $targetSlug)
    {
        if ($targetType === 'pin')
        {
            if ($methodType === 'favorite')
            {
                $virtualCoinService = new VirtualCoinService();
                return $virtualCoinService->rewardPin(
                    $object->slug,
                    $this->convertTargetCreatorSlug($targetType, $target),
                    $targetSlug
                );
            }
        }
        return true;
    }

    protected function preValidator($object, $target, $targetType, $methodType)
    {
        if ($targetType === 'pin')
        {
            if ($methodType === 'favorite')
            {
                if ($object->virtual_coin <= 0 && $object->money_coin <= 0)
                {
                    return '没有足够的团子';
                }
            }
        }
        return '';
    }

    protected function getCreatorSlug($target, $type)
    {
        switch ($type) {
            case 'user':
                return $target->slug;
            case 'pin':
                return $target->user_slug;
            case 'tag':
                return $target->creator_slug;
            case 'comment':
                return $target->from_user_slug;
            default:
                return '';
        }
    }

    protected function emitToggleEvent($object, $target, $targetType, $method, $result)
    {
        if ($targetType === 'user')
        {
            if ($method === 'follow')
            {
                event(new \App\Events\User\ToggleFollowUser($object, $target, $result));
            }
        }
        else if ($targetType === 'comment')
        {
            if ($method === 'up_vote')
            {
                event(new \App\Events\Comment\UpVote($target, $object, $result));
            }
        }
        else if ($targetType === 'pin')
        {
            if ($method === 'up_vote')
            {
                event(new \App\Events\Pin\UpVote($target, $object, $result));
            }
            else if ($method === 'favorite')
            {
                event(new \App\Events\Pin\Reward($target, $object));
            }
        }
    }

    protected function toggleAction($object, $target, $class, $type)
    {
        switch ($type) {
            case 'follow':
                return $object->toggleFollow($target, $class);
            case 'bookmark':
                return $object->toggleBookmark($target, $class);
            case 'like':
                return $object->toggleLike($target, $class);
            case 'favorite':
                return $object->toggleFavorite($target, $class);
            case 'subscribe':
                return $object->toggleSubscribe($target, $class);
            case 'up_vote':
                if ($object->hasUpvoted($target, $class))
                {
                    $object->cancelUpVote($target, $class);
                    return false;
                }
                $object->upvote($target, $class);
                return true;
            case 'down_vote':
                if ($object->hasDownvoted($target, $class))
                {
                    $object->cancelDownVote($target, $class);
                    return false;
                }
                $object->downvote($target, $class);
                return true;
            default:
                return null;
        }
    }

    protected function convertClass($type)
    {
        switch ($type) {
            case 'user':
                return User::class;
            case 'tag':
                return Tag::class;
            case 'pin':
                return Pin::class;
            case 'comment':
                return Comment::class;
            default:
                return null;
        }
    }

    protected function convertModel($type, $slug)
    {
        switch ($type) {
            case 'user':
                return User::where('slug', $slug)->first();
            case 'tag':
                return Tag::where('slug', $slug)->first();
            case 'pin':
                return Pin::where('slug', $slug)->first();
            case 'comment':
                return Comment::where('id', $slug)->first();
            default:
                return null;
        }
    }

    protected function convertTargetCreatorSlug($type, $target)
    {
        switch ($type) {
            case 'user':
                return $target->slug;
            case 'tag':
                return $target->creator_slug;
            case 'pin':
                return $target->user_slug;
            case 'comment':
                return $target->from_user_slug;
            default:
                return null;
        }
    }
}
