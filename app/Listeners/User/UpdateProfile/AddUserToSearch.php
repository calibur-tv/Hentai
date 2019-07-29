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

        if (null === $search)
        {
            Search::create([
                'type' => 3,
                'slug' => $user->slug,
                'text' => $text
            ]);
        }
        else
        {
            $search->update([
                'text' => $text
            ]);
        }
    }
}
