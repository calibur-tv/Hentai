<?php

namespace App\Models;

use App\Http\Modules\Counter\UnreadMessageCounter;
use App\Http\Modules\RichContentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Message extends Model
{
    protected $fillable = [
        'sender_slug',      // 触发消息的用户slug
        'getter_slug',      // 接受消息的用户slug
        'type',             // 消息的类型
    ];

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public function sender()
    {
        return $this->belongsTo('App\User', 'sender_slug', 'slug');
    }

    public static function createMessage(array $data)
    {
        $richContentService = new RichContentService();

        $content = $data['content'];
        $risk = $richContentService->detectContentRisk($content);
        if ($risk['risk_score'] > 0)
        {
            return null;
        }

        $getterSlug = $data['getter_slug'];
        $senderSlug = $data['sender_slug'];
        $messageType = $data['type'];
        $sender = $data['sender'];
        $message = self::create([
            'sender_slug' => $senderSlug,
            'getter_slug' => $getterSlug,
            'type' => $messageType
        ]);

        $content = $message->content()->create([
            'text' => $richContentService->saveRichContent($content)
        ]);

        $roomCacheKey = self::roomCacheKey($messageType, $getterSlug, $senderSlug);

        $getterMenu = MessageMenu::firstOrCreate([
            'sender_slug' => $senderSlug,
            'getter_slug' => $getterSlug,
            'type' => $messageType
        ]);
        $getterMenu->updateGetterMenu($roomCacheKey);

        $senderMenu = MessageMenu::firstOrCreate([
            'sender_slug' => $getterSlug,
            'getter_slug' => $senderSlug,
            'type' => $messageType
        ]);
        $senderMenu->updateSenderMenu($roomCacheKey);

        $UnreadMessageCounter = new UnreadMessageCounter();
        $UnreadMessageCounter->add($getterSlug);

        $messageData = [
            'user' => [
                'slug' => $sender->slug,
                'nickname' => $sender->nickname,
                'avatar' => $sender->avatar,
                'sex' => $sender->sex
            ],
            'content' => $richContentService->parseRichContent($content->text),
            'created_at' => $message->created_at
        ];
        if (Redis::EXISTS($roomCacheKey))
        {
            Redis::ZADD($roomCacheKey, $message->id, json_encode($messageData));
        }
        $messageData['id'] = $message->id;
        $messageData['channel'] = $roomCacheKey;

        return $messageData;
    }

    public static function roomCacheKey($type, $getterSlug, $senderSlug)
    {
        if ($type == 1)
        {
            if (strnatcmp($getterSlug, $senderSlug) > 0)
            {
                $tail = "{$getterSlug}@{$senderSlug}";
            }
            else
            {
                $tail = "{$senderSlug}@{$getterSlug}";
            }
        }
        else
        {
            $tail = $getterSlug;
        }
        return "channel@{$type}@{$tail}";
    }
}
