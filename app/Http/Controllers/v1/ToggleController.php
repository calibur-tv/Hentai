<?php

namespace App\Http\Controllers\v1;

use App\Events\User\ToggleFollowUser;
use App\Http\Controllers\Controller;
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
                Rule::in(['user', 'pin', 'tag']),
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

        $result = $this->toggleAction($object, $target, $targetClass, $methodType);
        if (null === $result)
        {
            return $this->resErrServiceUnavailable();
        }

        $result = empty($result['detached']);
        $this->emitToggleEvent($object, $target, $targetType, $methodType, $result);

        return $this->resOK($result);
    }

    protected function emitToggleEvent($object, $target, $targetType, $method, $result)
    {
        if ($targetType === 'user')
        {
            if ($method === 'follow')
            {
                event(new ToggleFollowUser($object, $target, $result));
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
                $object->cancelVote($target, $class);
                return $object->upvote($target, $class);
            case 'down_vote':
                $object->cancelVote($target, $class);
                return $object->downvote($target, $class);
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
            default:
                return null;
        }
    }
}
