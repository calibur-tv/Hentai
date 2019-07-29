<?php


namespace App\Http\Modules\Counter;


use App\Http\Repositories\UserRepository;
use App\Models\Search;
use App\User;

class UserPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('users');
    }

    public function boot($slug)
    {
        $user = User
            ::where('slug', $slug)
            ->first();

        if (is_null($user))
        {
            return [
                'visit_count' => 0,
                'followers_count' => 0,
                'following_count' => 0,
                'friends_count' => 0
            ];
        }

        $userRepository = new UserRepository();
        $friends = $userRepository->friends($slug);

        return [
            'visit_count' => $user->visit_count,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->followings()->count(),
            'friends_count' => $friends['total']
        ];
    }

    public function search($slug, $result)
    {
        Search
            ::where('slug', $slug)
            ->where('type', 3)
            ->update([
                'score' =>
                    $result['visit_count'] +
                    $result['followers_count'] +
                    $result['friends_count']
            ]);
    }
}
