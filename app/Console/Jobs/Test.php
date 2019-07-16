<?php

namespace App\Console\Jobs;

use App\Models\Tag;
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
        $tags = Tag::where('migration_state', 1)
            ->where('parent_slug', config('app.tag.notebook'))
            ->take(500)
            ->get();

        foreach ($tags as $tag)
        {
            $tag->rule()->delete();

            $tag->update([
                'migration_state' => 2
            ]);
        }
        return true;
    }
}
