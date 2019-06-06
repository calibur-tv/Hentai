<?php


namespace App\Http\Modules\Counter;


use App\Models\MessageMenu;

class UnReadMessageCounter extends AsyncCounter
{
    protected $from_user_slug;
    protected $message_type;

    public function __construct($fromUserSlug = 0, $messageType = 0)
    {
        parent::__construct('message_menus', 'count');
        $this->from_user_slug = $fromUserSlug;
        $this->message_type = $messageType;
    }

    protected function setDB($toUserSlug, $result)
    {
        MessageMenu
            ::where('from_user_slug', $this->from_user_slug)
            ->where('to_user_slug', $toUserSlug)
            ->where('type', $this->message_type)
            ->update([
                'count' => $result
            ]);
    }

    protected function readDB($toUserSlug)
    {
        return (int)MessageMenu
            ::where('to_user_slug', $toUserSlug)
            ->pluck('count')
            ->first();
    }
}
