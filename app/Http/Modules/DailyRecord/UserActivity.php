<?php


namespace App\Http\Modules\DailyRecord;

class UserActivity extends DailyRecord
{
    public function __construct()
    {
        // 木
        parent::__construct(1);
    }

    protected function hook($userSlug, $score)
    {

    }
}
