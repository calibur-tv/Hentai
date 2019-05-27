<?php


namespace App\Http\Modules\DailyRecord;


class TagExposure extends DailyRecord
{
    public function __construct()
    {
        parent::__construct(4);
    }

    protected function hook($userId, $score)
    {

    }
}
