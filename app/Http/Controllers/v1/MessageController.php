<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\UnreadMessageCounter;
use App\Http\Modules\WebSocketPusher;
use App\Http\Repositories\MessageRepository;
use App\Models\Message;
use App\Models\MessageMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
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
            'channel' => 'unread_total',
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

        $messageType = $channel[1];
        $getterSlug = $channel[2];
        if ($getterSlug === $senderSlug)
        {
            $getterSlug = $channel[3];
        }

        if ($messageType === 1 && $senderSlug === $getterSlug)
        {
            return $this->resErrBad();
        }

        $content = $request->get('content');
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

        return $this->resOK($cache);
    }

    public function getChatHistory(Request $request)
    {
        $channel = explode('@', $request->get('channel'));
        if (count($channel) < 4)
        {
            return $this->resErrBad();
        }
        $user = $request->user();

        $messageType = $channel[1];
        $getterSlug = $channel[2];
        $senderSlug = $user->slug;
        if ($getterSlug === $senderSlug)
        {
            $getterSlug = $channel[3];
        }
        $sinceId = intval($request->get('since_id'));
        $isUp = (boolean)$request->get('is_up') ?: false;
        $count = $request->get('count') ?: 15;

        $messageRepository = new MessageRepository();
        $result = $messageRepository->history($messageType, $getterSlug, $senderSlug, $sinceId, $isUp, $count);

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

    public function deleteMessageChannel(Request $request)
    {
        $channel = explode('@', $request->get('channel'));
        if (count($channel) < 4)
        {
            return $this->resErrBad();
        }

        $user = $request->user();
        $messageType = $channel[1];
        $senderSlug = $channel[2];
        $getterSlug = $user->slug;
        if ($senderSlug === $getterSlug)
        {
            $senderSlug = $channel[3];
        }

        MessageMenu
            ::where('type', $messageType)
            ->where('sender_slug', $senderSlug)
            ->where('getter_slug', $getterSlug)
            ->delete();

        $cacheKey = MessageMenu::messageListCacheKey($getterSlug);
        if (Redis::EXISTS($cacheKey))
        {
            Redis::ZREM(
                $cacheKey,
                Message::roomCacheKey($messageType, $getterSlug, $senderSlug)
            );
        }

        return $this->resNoContent();
    }
}
