<?php

namespace App\Listeners\User\Register;

use App\Models\Tag;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddDefaultTagRelation
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Register  $event
     * @return void
     */
    public function handle(\App\Events\User\Register $event)
    {
        $this->joinNewbieTopic($event->user);
        $this->createDefaultNotebook($event->user);
    }

    protected function joinNewbieTopic($user)
    {
        $tagSlug = config('app.tag.newbie');

        $user->bookmark(
            Tag::where('slug', $tagSlug)->first(),
            Tag::class
        );

        $user
            ->timeline()
            ->create([
                'event_type' => 1,
                'event_slug' => $tagSlug
            ]);
    }

    protected function createDefaultNotebook($user)
    {
        $parent = Tag
            ::where('slug', config('app.tag.notebook'))
            ->first();

        if (is_null($parent))
        {
            return;
        }

        Tag::createTag('默认专栏', $user, $parent);
    }
}
