<?php


namespace App\Http\Modules\DailyRecord;


class UserExposure extends DailyRecord
{
    public function __construct()
    {
        parent::__construct(2);
    }

    protected function hook($userId, $score)
    {

    }
}
