<?php


namespace App\Listeners\Bangumi\Pass;


class UpgradeUserLevel
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Bangumi\Pass $event)
    {
        $event->user->increment('level2');
    }
}
