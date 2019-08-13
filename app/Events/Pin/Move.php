<?php


namespace App\Events\Pin;


use App\Models\Pin;
use App\User;
use Illuminate\Queue\SerializesModels;

class Move
{
    use SerializesModels;

    public $pin;
    public $user;
    public $attachTags;
    public $detachTags;

    public function __construct(Pin $pin, User $user, array $tags)
    {
        $this->pin = $pin;
        $this->user = $user;
        $oldTags = $pin
            ->tags()
            ->pluck('slug')
            ->toArray();

        $this->attachTags = array_diff($tags, $oldTags);
        $this->detachTags = array_diff($oldTags, $tags);
    }
}
