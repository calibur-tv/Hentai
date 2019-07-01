<?php


namespace App\Http\Modules\DailyRecord;


use Illuminate\Support\Facades\DB;

class UserExposure extends DailyRecord
{
    public function __construct()
    {
        // 火
        parent::__construct(2);
    }

    protected function hook($userSlug, $score)
    {
        DB
            ::table('users')
            ->where('slug', $userSlug)
            ->update([
                'exposure_stat' => $this->get($userSlug, -1)
            ]);
    }
}
