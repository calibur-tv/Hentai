<?php


namespace App\Http\Modules\Counter;


use App\Models\MessageMenu;

class UnReadMessageCounter extends AsyncCounter
{
    protected $from_user_id;
    protected $message_type;

    public function __construct($fromUserId = 0, $messageType = 0)
    {
        parent::__construct('message_menus', 'count');
        $this->from_user_id = $fromUserId;
        $this->message_type = $messageType;
    }

    protected function setDB($toUserId, $result)
    {
        MessageMenu
            ::where('from_user_id', $this->from_user_id)
            ->where('to_user_id', $toUserId)
            ->where('type', $this->message_type)
            ->update([
                'count' => $result
            ]);
    }

    protected function readDB($toUserId)
    {
        return (int)MessageMenu
            ::where('to_user_id', $toUserId)
            ->pluck('count')
            ->first();
    }
}
