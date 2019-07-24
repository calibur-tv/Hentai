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
     * @param  \App\Events\User\Register  $event
     * @return void
     */
    public function handle(\App\Events\User\Register $event)
    {
        $this->joinNewbieTopic($event->user);
        $this->createDefaultNotebook($event->user);
    }

    protected function joinNewbieTopic($user)
    {
        event(new \App\Events\User\JoinZone($user, Tag::where('slug', config('app.tag.newbie'))->first()));
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
