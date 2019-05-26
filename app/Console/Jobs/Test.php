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
        $bangumis = DB
            ::table('bangumis')
            ->where('migration_state', 0)
            ->take(1000)
            ->get();

        if (empty($bangumis))
        {
            Log::info('all bangumi migration success');
        }

        foreach ($bangumis as $bangumi)
        {
            $alias = $bangumi->alias === 'null' ? '' : json_decode($bangumi->alias)->search;
            $intro = $bangumi->summary;
            $name = $bangumi->name;
            $parent_slug = '2he';

            $tag = Tag::create([
                'name' => $name,
                'parent_slug' => $parent_slug,
                'deep' => 2,
                'creator_id' => 1
            ]);

            $tag->extra()->create([
                'text' => json_encode([
                    'alias' => $alias ?: $name,
                    'intro' => $intro
                ])
            ]);

            $tag->update([
                'slug' => $this->id2slug($tag->id)
            ]);

            DB
                ::table('bangumis')
                ->where('id', $bangumi->id)
                ->update([
                    'migration_state' => 1
                ]);

            Log::info('bangumi ' . $bangumi->id . ' migration success');
        }

        return true;
    }

    protected function id2slug($id)
    {
        return base_convert(($id * 1000 + rand(0, 999)), 10, 36);
    }
}
