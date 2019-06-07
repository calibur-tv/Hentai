<?php


namespace App\Http\Modules\Counter;


use App\User;

class UserBeFollowedCounter extends SyncCounter
{
    public function __construct()
    {
        parent::__construct('user', 'be-followed');
    }

    protected function readDB($slug)
    {
        $user = User::where('slug', $slug)->first();

        return $user->followers()->count();
    }
}
