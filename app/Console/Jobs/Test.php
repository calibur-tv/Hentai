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
        $post = DB
            ::table('posts')
            ->where('migration_state', 0)
            ->take(1000)
            ->get()
            ->toArray();

        foreach ($post as $item)
        {
            $content = $item->content;
            $arr = explode('<p><br></p>', $content);
            $result = [];
            foreach ($arr as $row)
            {
                $row = str_replace('<p>', '', $row);
                $row = str_replace('</p>', '', $row);
                if ($row)
                {
                    $result[] = [
                        'type' => 'paragraph',
                        'data' => [
                            'text' => $row
                        ]
                    ];
                }
            }

            if (empty($result))
            {
                DB
                    ::table('posts')
                    ->where('id', $item->id)
                    ->update([
                        'migration_state' => 1
                    ]);
                continue;
            }

            $user = User::where('id', $item->user_id)->first();
            $tags = [
                config('app.tag.topic'),
                config('app.tag.newbie')
            ];

            Pin::createPin($result, 1, false, $user, $tags);

            DB
                ::table('posts')
                ->where('id', $item->id)
                ->update([
                    'migration_state' => 1
                ]);
        }

        return true;
    }
}
