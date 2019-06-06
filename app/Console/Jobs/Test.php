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
            ::where('migration_state', '<>', 1)
            ->withTrashed()
            ->take(3000)
            ->get();

        if (empty($users))
        {
            return true;
        }

        foreach ($users as $user)
        {
            $user->update([
                'slug' => 'cc-' . id2slug($user->id),
                'migration_state' => 1
            ]);
        }

        return true;
    }
}
