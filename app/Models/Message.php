<?php

namespace App\Models;

use App\Http\Modules\RichContentService;
use App\Http\Transformers\Message\MessageItemResource;
use Illuminate\Database\Eloquent\Model;

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

        $roomId = self::roomCacheKey($messageType, $getterSlug, $senderSlug);
        $message->content = $content;
        $message->sender = $sender;
        $message->channel = $roomId;

        event(new \App\Events\Message\Create($message, $sender, $roomId));

        return new MessageItemResource($message);
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
