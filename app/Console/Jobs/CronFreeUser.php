<?php

/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/1/2
 * Time: 下午8:49
 */

namespace App\Console\Jobs;

use App\Http\Repositories\UserRepository;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CronFreeUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronFreeUser';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'free blocked user';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $slugs = User
            ::where('banned_to', '<', Carbon::now())
            ->pluck('slug')
            ->toArray();

        if (empty($slugs))
        {
            return true;
        }

        User::whereIn('slug', $slugs)
            ->update([
                'banned_to' => null
            ]);

        $userRepository = new UserRepository();
        foreach ($slugs as $slug)
        {
            $userRepository->item($slug, true);
        }

        return true;
    }
}
