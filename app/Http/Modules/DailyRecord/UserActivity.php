<?php


namespace App\Http\Modules\DailyRecord;

use Illuminate\Support\Facades\DB;

class UserActivity extends DailyRecord
{
    public function __construct()
    {
        // 木
        parent::__construct(1);
    }

    protected function hook($userSlug, $score)
    {
        DB
            ::table('users')
            ->where('slug', $userSlug)
            ->update([
                'activity_stat' => $this->get($userSlug, -1)
            ]);
    }
}
