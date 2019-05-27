<?php


namespace App\Http\Modules\DailyRecord;

class TagActivity extends DailyRecord
{
    public function __construct()
    {
        parent::__construct(3);
    }

    protected function hook($tagId, $score)
    {

    }
}
