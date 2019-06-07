<?php


namespace App\Http\Modules\DailyRecord;

class PinActivity extends DailyRecord
{
    public function __construct()
    {
        parent::__construct(5);
    }

    protected function hook($tagSlug, $score)
    {

    }
}
