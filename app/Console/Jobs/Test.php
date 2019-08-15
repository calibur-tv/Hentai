<?php

namespace App\Console\Jobs;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\UserRepository;
use App\Models\Pin;
use App\Models\Tag;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $tags = Tag::where('migration_state', '<>', 7)
            ->take(1000)
            ->get();


        $userRepository = new UserRepository();
        foreach ($tags as $tag)
        {
            $user = $userRepository->item($tag->creator_slug);
            $tag->bookmark($user);
            $tag->update([
                'migration_state' => 7
            ]);
        }

        return true;
    }
}
