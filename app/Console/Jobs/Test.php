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
        $list = Pin
            ::where('content_type', 2)
            ->whereNull('published_at')
            ->pluck('slug')
            ->toArray();

        $pinRepository = new PinRepository();
        foreach ($list as $slug)
        {
            $pinRepository->item($slug, true);
        }

        return true;
    }
}
