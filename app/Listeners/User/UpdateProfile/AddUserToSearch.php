<?php


namespace App\Listeners\User\UpdateProfile;


use App\Models\Search;

class AddUserToSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\User\UpdateProfile $event)
    {
        $user = $event->user;
        $search = Search
            ::where('type', 0)
            ->where('slug', $user->slug)
            ->first();

        $text = $user->nickname . '|' . $user->signature;
        $score = $user->followers_count + $user->following_count + $user->visit_count;

        if (null === $search)
        {
            Search::create([
                'type' => 0,
                'slug' => $user->slug,
                'text' => $text,
                'score' => $score
            ]);
        }
        else
        {
            $search->update([
                'text' => $text,
                'score' => $score
            ]);
        }
    }
}
