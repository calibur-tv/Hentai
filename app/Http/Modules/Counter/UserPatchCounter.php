<?php


namespace App\Http\Modules\Counter;


use App\User;

class UserPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('users', [
            'visit_count',
            'followers_count',
            'following_count'
        ]);
    }

    public function boot($slug)
    {
        $user = User
            ::where('slug', $slug)
            ->first();

        return [
            'visit_count' => $user->visit_count,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->followings()->count()
        ];
    }
}
