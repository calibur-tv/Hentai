<?php

namespace App\Console\Jobs;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
            ->where('migration_state', '<>', 6)
            ->take(2000)
            ->get();

        foreach ($users as $user)
        {
            $user->createDefaultNotebook();
            $user->update([
                'migration_state' => 6
            ]);
        }

        return true;
    }
}
