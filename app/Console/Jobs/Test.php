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
        $pins = Pin::where('migration_state', 0)
            ->take(100)
            ->get();

        $pinRepository = new PinRepository();
        foreach ($pins as $pin)
        {
            $cache = $pinRepository->item($pin->slug);
            $mainAreaSlug = $cache->area ? $cache->area->slug : '';
            $mainTopicSlug = $cache->topic ? $cache->topic->slug : '';
            $mainNotebookSlug = $cache->notebook ? $cache->notebook->slug : '';
            $pin->update([
                'main_area_slug' => $mainAreaSlug,
                'main_topic_slug' => $mainTopicSlug,
                'main_notebook_slug' => $mainNotebookSlug,
                'migration_state' => 1
            ]);
        }

        return true;
    }
}
