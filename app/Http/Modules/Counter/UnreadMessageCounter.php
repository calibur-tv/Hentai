<?php


namespace App\Http\Modules\Counter;


use App\Models\MessageMenu;

class UnreadMessageCounter extends AsyncCounter
{
    protected $sender_slug;
    protected $message_type;

    public function __construct($senderSlug = 0, $messageType = 0)
    {
        parent::__construct('message_menus_message', 'count');
        $this->sender_slug = $senderSlug;
        $this->message_type = $messageType;
    }

    protected function setDB($getterSlug, $result)
    {
        MessageMenu
            ::where('sender_slug', $this->sender_slug)
            ->where('getter_slug', $getterSlug)
            ->where('type', $this->message_type)
            ->update([
                'count' => $result
            ]);
    }

    protected function readDB($getterSlug)
    {
        return (int)MessageMenu
            ::where('getter_slug', $getterSlug)
            ->pluck('count')
            ->first();
    }
}
