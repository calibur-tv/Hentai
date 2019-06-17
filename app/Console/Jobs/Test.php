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
            ::where('migration_state', '<>', 5)
            ->take(2000)
            ->get();

        foreach ($users as $user)
        {
            $user->bookmark(
                Tag::where('slug', config('app.tag.newbie'))->first(),
                Tag::class
            );

            $user->update([
                'migration_state' => 5
            ]);

            Log::info('user ' . $user->id . ' migration success');
        }

        return true;
    }
}
