<?php


namespace App\Listeners\Tag\Create;


use App\Http\Repositories\TagRepository;
use App\Models\Search;

class AddTagToSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Tag\Create $event)
    {
        $tag = $event->tag;
        $tagRepository = new TagRepository();
        $txtTag = $tagRepository->item($tag->slug);

        Search::create([
            'type' => 1,
            'slug' => $tag->slug,
            'text' => $txtTag->alias
        ]);
    }
}
