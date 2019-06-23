<?php

namespace App\Console\Jobs;

use App\Models\Tag;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test job';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User
            ::withTrashed()
            ->where('migration_state', '<>', 1)
            ->take(2000)
            ->get();

        foreach ($users as $user)
        {
            $user->timeline()->create([
                'event_type' => 0,
                'event_slug' => ''
            ]);

            $user->markTag(config('app.tag.newbie'));

            $user->update([
                'migration_state' => 1
            ]);

            Log::info('user：' . $user->slug . ' migration success');
        }

        return true;
    }
}
