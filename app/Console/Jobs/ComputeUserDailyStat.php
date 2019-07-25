<?php

/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/1/2
 * Time: 下午8:49
 */

namespace App\Console\Jobs;

use App\Http\Modules\DailyRecord\UserActivity;
use App\Http\Modules\DailyRecord\UserExposure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeUserDailyStat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ComputeUserDailyStat';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'compute user daily stat';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $userActivity = new UserActivity();
        $userExposure = new UserExposure();

        DB
            ::table('users')
            ->orderBy('id')
            ->where('level', '>', 1)
            ->select('slug')
            ->chunk(100, function($users) use ($userActivity, $userExposure)
            {
                foreach ($users as $user)
                {
                    $userActivity->migrate($user->slug);
                    $userExposure->migrate($user->slug);
                }
            });

        return true;
    }
}
