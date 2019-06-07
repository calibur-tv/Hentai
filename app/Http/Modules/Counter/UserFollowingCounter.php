<?php


namespace App\Http\Modules\Counter;


use App\User;

class UserFollowingCounter extends SyncCounter
{
    public function __construct()
    {
        parent::__construct('user', 'following');
    }

    protected function readDB($slug)
    {
        $user = User::where('slug', $slug)->first();

        return $user->followings()->count();
    }
}
