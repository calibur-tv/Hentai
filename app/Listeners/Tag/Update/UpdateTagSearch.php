<?php


namespace App\Listeners\Tag\Update;


use App\Http\Repositories\TagRepository;
use App\Models\Search;

class UpdateTagSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Tag\Update $event)
    {
        $tag = $event->tag;
        $tagRepository = new TagRepository();
        $txtTag = $tagRepository->item($tag->slug);

        $search = Search
            ::where('type', 1)
            ->where('slug', $tag->slug)
            ->first();

        $text = $txtTag->alias;

        if (!$search)
        {
            Search::create([
                'type' => 1,
                'slug' => $tag->slug,
                'text' => $text
            ]);
            return;
        }

        $search->update([
            'text' => $text
        ]);
    }
}
