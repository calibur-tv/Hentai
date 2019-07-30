<?php

namespace App\Console\Jobs;

use App\Http\Repositories\TagRepository;
use App\Models\Search;
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
        $tags = Tag
            ::where('migration_state', '<>', 6)
            ->whereIn('parent_slug', [
                config('app.tag.topic'),
                config('app.tag.bangumi'),
                config('app.tag.game')
            ])
            ->take(200)
            ->get();

        $tagRepository = new TagRepository();
        foreach ($tags as $tag)
        {
            $txtTag = $tagRepository->item($tag->slug);

            Search::create([
                'type' => 1,
                'slug' => $tag->slug,
                'text' => $txtTag->name
            ]);

            $tag->update([
                'migration_state' => 6
            ]);
        }

        return true;
    }
}
