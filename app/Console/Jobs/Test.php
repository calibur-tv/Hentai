<?php

namespace App\Console\Jobs;

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
        Pin
            ::where('content_type', 2)
            ->whereHas('tags', function ($query)
            {
                $query->where('parent_slug', config('app.tag.notebook'));
            })
            ->delete();

        return true;
    }
}
