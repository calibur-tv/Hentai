<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\RichContentService;
use App\Http\Modules\VirtualCoinService;
use App\Models\Comment;
use App\Models\Pin;
use App\Models\PinAnswer;
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
        $errorMessage = $this->preValidator($user, $target, $targetType, $methodType);
        if ($errorMessage)
        {
            return $this->resErrBad($errorMessage);
        }

        $result = $this->preToggleAction($user, $target, $targetType, $methodType, $targetSlug, $actionSlug);
        if (false === $result)
        {
            return $this->resErrServiceUnavailable();
        }
        if (null === $result)
        {
            return $this->resOK(0);
        }

        $result = $this->toggleAction($user, $target, $targetClass, $methodType);
        if (null === $result)
        {
            return $this->resErrServiceUnavailable();
        }

        $result = gettype($result) === 'array' ? empty($result['detached']) : $result;
        $this->emitToggleEvent($user, $target, $targetType, $methodType, $result);

        return $this->resOK($result ? 1 : -1);
    }

    public function vote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin_slug' => 'required|string',
            'answer_hash' => 'required|array'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $pinSlug = $request->get('pin_slug');
        $pin = Pin
            ::where('slug', $pinSlug)
            ->with(['content' => function ($query)
            {
                $query->orderBy('created_at', 'desc');
            }])
            ->first();

        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        $richContentService = new RichContentService();
        $vote = $richContentService->getFirstType($pin->content->text, 'vote');
        if (is_null($vote))
        {
            return $this->resErrBad('投票已删除');
        }

        $answers = array_map(function ($item)
        {
            return $item['id'];
        }, $vote['items']);

        $requestAnswers = $request->get('answer_hash');
        if (!empty(array_diff($requestAnswers, $answers)))
        {
            return $this->resErrBad('投票已过期');
        }

        if ($vote['expired_at'] && $vote['expired_at'] < time())
        {
            return $this->resErrBad($vote['expired_at']);
        }

        $user = $request->user();
        $pinAnswer = PinAnswer
            ::where('pin_slug', $pinSlug)
            ->where('user_slug', $user->slug)
            ->first();

        if (is_null($pinAnswer))
        {
            PinAnswer::create([
                'pin_slug' => $pinSlug,
                'user_slug' => $user->slug,
                'selected_uuid' => json_encode($requestAnswers),
                'is_right' => (bool)count(array_intersect($requestAnswers, $vote['right_ids']))
            ]);

            event(new \App\Events\Pin\Vote($pin, $user, $requestAnswers));
        }
        else
        {
            $oldAnswer = json_decode($pinAnswer->selected_uuid, true);
            $pinAnswer->update([
                'selected_uuid' => json_encode($requestAnswers),
                'is_right' => (bool)count(array_intersect($requestAnswers, $vote['right_ids']))
            ]);

            event(new \App\Events\Pin\ReVote($pin, $user, $requestAnswers, $oldAnswer));
        }

        return $this->resNoContent();
    }

    protected function preToggleAction($user, $target, $targetType, $methodType, $targetSlug, $actionSlug)
    {
        if ($targetType === 'pin')
        {
            if ($methodType === 'favorite')
            {
                $virtualCoinService = new VirtualCoinService();
                return $virtualCoinService->rewardPin(
                    $user->slug,
                    $this->getCreatorSlug($target, $targetType),
                    $targetSlug
                );
            }
            else if ($methodType === 'bookmark')
            {
                $result = true;

                $tag = $target
                    ->tags()
                    ->where('parent_slug', config('app.tag.notebook'))
                    ->where('creator_slug', $user->slug)
                    ->first();
                if ($tag)
                {
                    $tag->removePin($target, $user);
                    if (!$actionSlug)
                    {
                        return true;
                    }

                    $result = null;
                }

                $tag = Tag
                    ::where('slug', $actionSlug)
                    ->first();

                if (is_null($tag))
                {
                    return false;
                }
                $tag->addPin($target, $user);

                return $result;
            }
        }
        return true;
    }

    protected function preValidator($user, $target, $targetType, $methodType)
    {
        if ($user->slug === $this->getCreatorSlug($target, $targetType))
        {
            return '自己的内容';
        }

        if ($targetType === 'pin')
        {
            if ($methodType === 'favorite')
            {
                if ($user->virtual_coin <= 0 && $user->money_coin <= 0)
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

    protected function emitToggleEvent($user, $target, $targetType, $method, $result)
    {
        if ($targetType === 'user')
        {
            if ($method === 'follow')
            {
                event(new \App\Events\User\ToggleFollowUser($target, $user, $result));
            }
        }
        else if ($targetType === 'comment')
        {
            if ($method === 'up_vote')
            {
                event(new \App\Events\Comment\UpVote($target, $user, $result));
            }
        }
        else if ($targetType === 'pin')
        {
            if ($method === 'up_vote')
            {
                event(new \App\Events\Pin\UpVote($target, $user, $result));
            }
            else if ($method === 'favorite')
            {
                event(new \App\Events\Pin\Reward($target, $user));
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
                if ($user->hasUpvoted($target, $class))
                {
                    $user->cancelUpVote($target, $class);
                    return false;
                }
                $user->upvote($target, $class);
                return true;
            case 'down_vote':
                if ($user->hasDownvoted($target, $class))
                {
                    $user->cancelDownVote($target, $class);
                    return false;
                }
                $user->downvote($target, $class);
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
}
