<?php

namespace App\Models;

use App\Http\Modules\Counter\UnReadMessageCounter;
use App\Http\Modules\RichContentService;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'from_user_id', // 触发消息的用户id
        'to_user_id',   // 接受消息的用户id
        'type',         // 消息的类型
    ];

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public static function createMessage(array $data, array $content)
    {
        $message = self::create($data);
        $richContentService = new RichContentService();
        $message->content()->create([
            'text' => $richContentService->saveRichContent($content)
        ]);
        $messageMenu = MessageMenu::firstOrCreate([
            'from_user_id' => $data['from_user_id'],
            'to_user_id' => $data['to_user_id'],
            'type' => $data['type']
        ]);
        $messageMenu->increment('count');
        $unReadMessageCounter = new UnReadMessageCounter();
        $unReadMessageCounter->add($data['to_user_id']);

        return $message;
    }
}
