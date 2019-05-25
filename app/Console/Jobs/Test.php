<?php

namespace App\Console\Jobs;

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
            ::where('migration_state', 0)
            ->take(100)
            ->get();

        foreach ($users as $user)
        {
            $user->createApiToken();
            $user->update([
                'slug' => $this->id2slug($user->id),
                'migration_state' => 1
            ]);
        }

        return true;
    }

    protected function id2slug($id)
    {
        return base_convert(($id * 1000 + rand(0, 999)), 10, 36);
    }
}
