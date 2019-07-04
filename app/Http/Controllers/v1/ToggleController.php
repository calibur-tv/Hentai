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
            'model_type' => [
                'required',
                Rule::in(['user', 'pin', 'tag']),
            ],
            'action_type' => [
                'required',
                Rule::in(['like', 'bookmark', 'follow', 'favorite', 'subscribe', 'up_vote', 'down_vote']),
            ],
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $targetSlug = $request->get('target_slug');
        $actionType = $request->get('action_type');
        $modelType = $request->get('model_type');

        $targetClass = $this->convertTargetClass($modelType);
        if (is_null($targetClass))
        {
            return $this->resErrBad();
        }

        $targetModel = $this->getTargetModel($modelType, $targetSlug);
        if (is_null($targetClass))
        {
            return $this->resErrNotFound();
        }

        $user = $request->user();
        $result = $this->toggleAction($user, $targetModel, $targetClass, $actionType);
        if (null === $result)
        {
            return $this->resErrServiceUnavailable();
        }

        $this->emitToggleEvent($user, $targetModel, $targetClass, $modelType, $actionType, $result);

        return $this->resNoContent();
    }

    protected function emitToggleEvent($user, $target, $class, $model, $action, $result)
    {
        if ($model === 'user')
        {
            if ($action === 'follow')
            {
                event(new ToggleFollowUser($user, $target, $class, $model, $action, $result));
            }
        }
    }

    protected function toggleAction($user, $target, $class, $type)
    {
        switch ($type) {
            case 'follow':
                return $user->toggleFollow($target, $class);
            case 'bookmark':
                return $user->toggleBookmark($target, $class);
            case 'like':
                return $user->toggleLike($target, $class);
            case 'favorite':
                return $user->toggleFavorite($target, $class);
            case 'subscribe':
                return $user->toggleSubscribe($target, $class);
            case 'up_vote':
                $user->cancelVote($target, $class);
                return $user->upvote($target, $class);
            case 'down_vote':
                $user->cancelVote($target, $class);
                return $user->downvote($target, $class);
            default:
                return null;
        }
    }

    protected function convertTargetClass($type)
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

    protected function getTargetModel($type, $slug)
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

    protected function convertUserRelation($currentFollowTarget, $targetFollowCurrent)
    {
        // 'friend', 'follower', 'following', 'stranger'
        if ($currentFollowTarget && $targetFollowCurrent)
        {
            $result = 'friend';
        }
        else if ($currentFollowTarget && !$targetFollowCurrent)
        {
            $result = 'following';
        }
        else if (!$currentFollowTarget && $targetFollowCurrent)
        {
            $result = 'follower';
        }
        else
        {
            $result = 'stranger';
        }

        return $result;
    }
}
