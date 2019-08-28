<?php

namespace App\Listeners\Pin\Create;

use App\Http\Modules\Counter\TagPatchCounter;
use App\Http\Repositories\FlowRepository;
use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddPinTagRelation implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Pin\Create  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        $arr = array_filter($event->tags, function ($item)
        {
            return $item;
        });
        if (empty($arr))
        {
            return;
        }

        $tags = [];
        $tagRepository = new TagRepository();
        foreach ($arr as $slug)
        {
            $tags = array_merge($tags, $tagRepository->receiveTagChain($slug));
        }
        $tags = array_unique($tags);
        if (empty($arr))
        {
            return;
        }

        $tagIds = array_map(function ($slug)
        {
            return slug2id($slug);
        }, $tags);

        $event->pin->tags()->attach($tagIds);

        if (!$event->doPublish || $event->pin->content_type !== 1)
        {
            return;
        }

        $flowRepository = new FlowRepository();
        $tagPatchCounter = new TagPatchCounter();
        $slug = $event->pin->slug;

        foreach ($tags as $tagSlug)
        {
            $flowRepository->add_pin($tagSlug, $slug);
            $tagPatchCounter->add($tagSlug, 'pin_count');
        }
    }
}
