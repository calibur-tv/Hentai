<?php


namespace App\Listeners\User\JoinZone;


class UpgradeUserLevel
{
    public function __construct()
    {

    }

    public function handle(\App\Events\User\JoinZone $event)
    {
        $event->user->increment('level');
    }
}
