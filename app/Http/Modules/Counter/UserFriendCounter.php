<?php


namespace App\Http\Modules\Counter;


use App\Http\Repositories\UserRepository;

class UserFriendCounter extends SyncCounter
{
    public function __construct()
    {
        parent::__construct('user', 'friend');
    }

    protected function readDB($slug)
    {
        $userRepository = new UserRepository();
        $list = $userRepository->friends($slug);

        return count($list);
    }
}
