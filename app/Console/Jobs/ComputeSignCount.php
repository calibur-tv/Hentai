<?php

/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/1/2
 * Time: 下午8:49
 */

namespace App\Console\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ComputeSignCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ComputeSignCount';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'compute continuous sign count';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        User
            ::where('latest_signed_at', '<', Carbon::now()->yesterday())
            ->decrement('continuous_sign_count');

        return true;
    }
}
