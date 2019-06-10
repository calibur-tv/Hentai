<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\UnreadMessageCounter;
use App\Http\Modules\WebSocketPusher;
use App\Http\Repositories\MessageRepository;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel' => 'required|string',
            'content' => 'required|array'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $sender = $request->user();
        $senderSlug = $sender->slug;
        $channel = explode('@', $request->get('channel'));
        if (count($channel) < 4)
        {
            return $this->resErrBad();
        }

        $messageType = $channel[2];
        $getterSlug = $channel[3];
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
        $webSocketPusher->pushUserMessageList($getterSlug);
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

        return $this->resOK($cache);
    }

    public function getChatHistory(Request $request)
    {
        $channel = explode('@', $request->get('channel'));
        if (count($channel) < 4)
        {
            return $this->resErrBad();
        }

        $messageType = $channel[2];
        $getterSlug = $channel[3];
        $user = $request->user();
        $sinceId = intval($request->get('since_id'));
        $isUp = (boolean)$request->get('is_up') ?: false;
        $count = $request->get('count') ?: 15;

        $messageRepository = new MessageRepository();
        $result = $messageRepository->history($messageType, $getterSlug, $user->slug, $sinceId, $isUp, $count);

        return $this->resOK($result);
    }

    public function getMessageChannel(Request $request)
    {
        $user = $request->user();
        $slug = $request->get('slug');
        $type = $request->get('type');

        $channel = Message::roomCacheKey($type, $slug, $user->slug);

        return $this->resOK($channel);
    }
}
