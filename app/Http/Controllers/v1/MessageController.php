<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\UnReadMessageCounter;
use App\Http\Modules\RichContentService;
use App\Http\Modules\WebSocketPusher;
use App\Http\Repositories\UserRepository;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function getMessageTotal(Request $request)
    {
        $slug = $request->get('slug');
        if (!$slug)
        {
            return $this->resErrBad();
        }

        $unReadMessageCounter = new UnReadMessageCounter();

        return $this->resOK([
            'unread_message_total' => $unReadMessageCounter->get($slug),
            'unread_notice_total' => 0
        ]);
    }

    /**
     * 发一个消息
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_type' => [
                'required',
                Rule::in([1, 2, 3]),
            ],
            'target_slug' => 'required|string',
            'content' => 'required|array'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $messageType = $request->get('message_type');
        $fromUser = $request->user();
        $fromUserSlug = $fromUser->slug;
        $targetSlug = $request->get('target_slug');
        $content = $request->get('content');
        if ($messageType === 1 && $fromUserSlug === $targetSlug)
        {
            return $this->resErrBad();
        }

        $richContentService = new RichContentService();
        // TODO 敏感词过滤

        Message::createMessage([
            'from_user_slug' => $fromUserSlug,
            'to_user_slug' => $targetSlug,
            'type' => $messageType,
            'content' => $richContentService->saveRichContent($content)
        ]);

        $webSocketPusher = new WebSocketPusher();
        $webSocketPusher->pushUnReadMessage($targetSlug);

        return $this->resNoContent();
    }

    public function getMessageMenu(Request $request)
    {
        $user = $request->user();
        $userRepository = new UserRepository();

        $cache = $userRepository->messageMenu($user->slug);
        if (empty($cache))
        {
            return [];
        }

        foreach ($cache as $i => $item)
        {
            if ($item['type'] == 1)
            {
                $cache[$i]['from'] = $userRepository->item($item['slug']);
            }
        }

        return $this->resOK($cache);
    }

    public function getChatHistory(Request $request)
    {
        $user = $request->user();
        $type = $request->get('message_type');
        $fromUserSlug = $request->get('from_user_slug');
        $sinceId = intval($request->get('since_id'));
        $take = $request->get('take') ?: 15;

        $messages = Message
            ::where('to_user_slug', $user->slug)
            ->where('from_user_slug', $fromUserSlug)
            ->where('type', $type)
            ->when(!$sinceId, function ($query, $sinceId)
            {
                return $query->where('id', '<', $sinceId);
            })
            ->orderBy('created_at', 'DESC')
            ->take($take)
            ->with('content:text')
            ->get()
            ->toArray();

        if (empty($messages))
        {
            return $this->resOK([
                'total' => 0,
                'no_more' => true,
                'result' => []
            ]);
        }

        $userRepository = new UserRepository();
        $richContentService = new RichContentService();

        foreach ($messages as $i => $msg)
        {
            $messages[$i]['user'] = $userRepository->item($msg['from_user_slug']);
            $messages[$i]['content'] = $richContentService->parseRichContent($msg['content']);
        }

        return $this->resOK([
            'total' => 0,
            'no_more' => count($messages) < $take,
            'result' => $messages
        ]);
    }
}
