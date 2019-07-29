<?php


namespace App\Events\User;


use App\Models\Tag;
use App\User;
use Illuminate\Queue\SerializesModels;

class JoinZone
{
    use SerializesModels;

    public $user;
    public $tag;

    public function __construct(User $user, Tag $tag)
    {
        $this->user = $user;
        $this->tag = $tag;

        $user->bookmark($tag, Tag::class);
        $user->timeline()->create([
            'event_type' => 1,
            'event_slug' => $tag->slug
        ]);
    }
}
