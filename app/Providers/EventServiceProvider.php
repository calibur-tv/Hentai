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
        ],
        'App\Events\Pin\Update' => [
            'App\Listeners\Pin\Update\Trial',
            'App\Listeners\Pin\Update\UpdatePinTimeline',
            'App\Listeners\Pin\Update\RefreshCache',
        ],
    ];
}
