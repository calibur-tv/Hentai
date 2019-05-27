<?php


namespace App\Http\Modules\DailyRecord;

class UserActivity extends DailyRecord
{
    public function __construct()
    {
        parent::__construct(1);
    }

    protected function hook($userId, $score)
    {

    }
}
