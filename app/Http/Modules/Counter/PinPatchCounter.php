<?php


namespace App\Http\Modules\Counter;


class PinPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('pins', [
            'visit_count',
            'comment_count',
            'like_count',
            'mark_count',
            'reward_count'
        ]);
    }
}
