<?php


namespace App\Http\Modules\DailyRecord;


class UserExposure extends DailyRecord
{
    public function __construct()
    {
        // 火
        parent::__construct(2);
    }

    protected function hook($userSlug, $score)
    {

    }
}
