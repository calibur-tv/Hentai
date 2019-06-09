<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\UnreadMessageCounter;
use App\Http\Modules\WebSocketPusher;
use App\Http\Repositories\MessageRepository;
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

        $UnreadMessageCounter = new UnreadMessageCounter();

        return $this->resOK([
            'unread_message_total' => $UnreadMessageCounter->get($slug),
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
            'getter_slug' => 'required|string',
            'content' => 'required|array'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $messageType = $request->get('message_type');
        $sender = $request->user();
        $senderSlug = $sender->slug;
        $getterSlug = $request->get('getter_slug');
        $content = $request->get('content');
        if ($messageType === 1 && $senderSlug === $getterSlug)
        {
            return $this->resErrBad();
        }

        // TODO 敏感词过滤

        $message = Message::createMessage([
            'sender_slug' => $senderSlug,
            'getter_slug' => $getterSlug,
            'type' => $messageType,
            'content' => $content,
            'sender' => $sender
        ]);

        $webSocketPusher = new WebSocketPusher();
        $webSocketPusher->pushUnreadMessage($getterSlug);
        $webSocketPusher->pushChatMessage($getterSlug, $message);

        return $this->resCreated($message);
    }

    public function getMessageMenu(Request $request)
    {
        $user = $request->user();
        $messageRepository = new MessageRepository();

        $cache = $messageRepository->menu($user->slug);
        if (empty($cache))
        {
            return [];
        }

        $userRepository = new UserRepository();
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
        $getterSlug = $request->get('getter_slug');
        $sinceId = intval($request->get('since_id'));
        $isUp = (boolean)$request->get('is_up') ?: false;
        $count = $request->get('count') ?: 15;

        $messageRepository = new MessageRepository();
        $result = $messageRepository->history($type, $getterSlug, $user->slug, $sinceId, $isUp, $count);

        if (empty($result))
        {
            return $this->resOK([
                'total' => 0,
                'no_more' => true,
                'result' => []
            ]);
        }

        return $this->resOK($result);
    }
}
