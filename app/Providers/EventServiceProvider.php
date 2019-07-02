<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\User\Register' => [
            'App\Listeners\User\Register\InitUserTimeline',
            'App\Listeners\User\Register\AddDefaultTagRelation',
        ],
        'App\Events\Pin\Create' => [
            'App\Listeners\Pin\Create\Trial',
            'App\Listeners\Pin\Create\InitPinTimeline',
            'App\Listeners\Pin\Create\AddPinTagRelation',
            'App\Listeners\Pin\Create\RefreshUserDrafts',
            'App\Listeners\Pin\Create\UpdateAuthorTimeline',
        ],
        'App\Events\Pin\Update' => [
            'App\Listeners\Pin\Update\Trial',
            'App\Listeners\Pin\Update\UpdatePinTimeline',
            'App\Listeners\Pin\Update\RefreshCache',
            'App\Listeners\Pin\Update\UpdateAuthorTimeline',
        ],
        'App\Events\Pin\Delete' => [
            'App\Listeners\Pin\Delete\UpdatePinTimeline',
            'App\Listeners\Pin\Delete\UpdateAuthorTimeline',
            'App\Listeners\Pin\Delete\RefreshCache',
        ],
        'App\Events\Tag\Create' => [
            'App\Listeners\Tag\Create\InitTagTimeline',
            'App\Listeners\Tag\Create\UpdateCreatorBookmark',
            'App\Listeners\Tag\Create\UpdateCreatorTimeline',
            'App\Listeners\Tag\Create\RefreshParentCache',
        ],
        'App\Events\Tag\Update' => [
            'App\Listeners\Tag\Update\UpdateTagTimeline',
            'App\Listeners\Tag\Update\RefreshCache',
        ],
        'App\Events\Tag\Delete' => [
            'App\Listeners\Tag\Delete\UpdateTagTimeline',
            'App\Listeners\Tag\Delete\UpdateCreatorTimeline',
            'App\Listeners\Tag\Delete\UpdateUsersBookmark',
            'App\Listeners\Tag\Delete\MoveChildrenToTrash',
            'App\Listeners\Tag\Delete\RefreshCache',
        ],
    ];
}
