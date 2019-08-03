<?php

namespace App\Console\Jobs;

use App\User;
use Illuminate\Console\Command;

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
            ::where('migration_state', '<>', 4)
            ->take(1000)
            ->get();

        foreach ($users as $user)
        {
            $level = $user
                ->tags()
                ->whereIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.game'),
                    config('app.tag.topic')
                ])
                ->count();

            $user->update([
                'level' => $level,
                'migration_state' => 4
            ]);
        }

        return true;
    }
}
