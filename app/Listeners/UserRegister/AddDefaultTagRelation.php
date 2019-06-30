<?php

namespace App\Listeners\UserRegister;

use App\Events\UserRegister;
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
     * @param  UserRegister  $event
     * @return void
     */
    public function handle(UserRegister $event)
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
        $parentSlug = config('app.tag.notebook');
        $name = '默认专栏';

        $tag = Tag::createTag(
            [
                'name' => $name,
                'parent_slug' => $parentSlug,
                'creator_slug' => $user->slug,
                'deep' => 2
            ],
            [
                'alias' => $name,
                'intro' => ''
            ]
        );

        $user->bookmark($tag, Tag::class);
    }
}
