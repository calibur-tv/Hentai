<?php


namespace App\Http\Modules\Counter;


class TagPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('tags', [
            'pin_count',
            'seen_user_count',
            'followers_count',
            'activity_stat',
        ]);
    }
}
