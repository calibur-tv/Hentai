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
            ::table('scores')
            ->where('migration_state', '<>', 5)
            ->take(1000)
            ->get()
            ->toArray();

        foreach ($post as $item)
        {
            $arr = json_decode($item->content, true);
            $result = [
                [
                    'type' => 'title',
                    'data' => [
                        'text' => $item->title
                    ]
                ]
            ];
            foreach ($arr as $row)
            {
                if ($row['type'] === 'txt' || $row['type'] === 'use')
                {
                    $result[] = [
                        'type' => 'paragraph',
                        'data' => [
                            'text' => $row['text']
                        ]
                    ];
                }
                else if ($row['type'] === 'img')
                {
                    $result[] = [
                        'type' => 'image',
                        'data' => [
                            'caption' => $row['text'],
                            'withBorder' => false,
                            'stretched' => false,
                            'withBackground' => false,
                            'file' => [
                                'height' => $row['height'],
                                'width' => $row['width'],
                                'size' => $row['size'],
                                'mime' => $row['mime'],
                                'url' => $row['url']
                            ]
                        ]
                    ];
                }
                else if ($row['type'] === 'title')
                {
                    $result[] = [
                        'type' => 'header',
                        'data' => [
                            'level' => 2,
                            'text' => $row['text']
                        ]
                    ];
                }
                else if ($row['type'] === 'list')
                {
                    $result[] = [
                        'type' => 'list',
                        'data' => [
                            'style' => $row['sort'] ? 'ordered' : 'unordered',
                            'items' => explode('\n', $row['text'])
                        ]
                    ];
                }
            }

            $user = User::where('id', $item->user_id)->first();

            if (count($result) === 1 || !$user)
            {
                DB
                    ::table('scores')
                    ->where('id', $item->id)
                    ->update([
                        'migration_state' => 5
                    ]);
                continue;
            }

            $tags = [
                config('app.tag.topic'),
                config('app.tag.newbie')
            ];

            Pin::createPin($result, 1, false, $user, $tags);

            DB
                ::table('scores')
                ->where('id', $item->id)
                ->update([
                    'migration_state' => 5
                ]);
        }

        return true;
    }
}
