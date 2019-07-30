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
        if (
            !in_array($tag->parent_slug, [
                config('app.tag.topic'),
                config('app.tag.bangumi'),
                config('app.tag.game')
            ])
        )
        {
            return;
        }

        $tagRepository = new TagRepository();
        $txtTag = $tagRepository->item($tag->slug);

        Search::create([
            'type' => 1,
            'slug' => $tag->slug,
            'text' => $txtTag->alias
        ]);
    }
}
