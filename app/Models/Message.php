<?php

namespace App\Models;

use App\Http\Modules\Counter\UnReadMessageCounter;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'from_user_slug', // 触发消息的用户slug
        'to_user_slug',   // 接受消息的用户slug
        'type',           // 消息的类型
    ];

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public static function createMessage(array $data)
    {
        $message = self::create([
            'from_user_slug' => $data['from_user_slug'],
            'to_user_slug' => $data['to_user_slug'],
            'type' => $data['type']
        ]);
        $message->content()->create([
            'text' => $data['content']
        ]);

        $messageMenu = MessageMenu::firstOrCreate([
            'from_user_slug' => $data['from_user_slug'],
            'to_user_slug' => $data['to_user_slug'],
            'type' => $data['type']
        ]);
        $messageMenu->updateMsgMenu();

        $unReadMessageCounter = new UnReadMessageCounter();
        $unReadMessageCounter->add($data['to_user_slug']);

        return $message;
    }
}
