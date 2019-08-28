<?php

namespace App\Listeners\Pin\Update;

use App\Http\Modules\Counter\TagPatchCounter;
use App\Http\Repositories\FlowRepository;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatePinTagRelation implements ShouldQueue
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
     * @param  \App\Events\Pin\Update  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Update $event)
    {
        $pin = $event->pin;
        $arr = array_filter($event->tags, function ($item)
        {
            return $item;
        });

        $newTagSlugs = [];
        $tagRepository = new TagRepository();
        foreach ($arr as $slug)
        {
            $newTagSlugs = array_merge($newTagSlugs, $tagRepository->receiveTagChain($slug));
        }

        $newTagSlugs = array_unique($newTagSlugs);
        $oldTagSlugs = $pin
            ->tags()
            ->pluck('slug')
            ->toArray();

        $attachTags = array_diff($newTagSlugs, $oldTagSlugs);
        $detachTags = array_diff($oldTagSlugs, $newTagSlugs);

        if (!empty($detachTags))
        {
            $detachIds = array_map(function ($slug)
            {
                return slug2id($slug);
            }, $detachTags);
            $pin->tags()->detach($detachIds);
        }

        if (!empty($attachTags))
        {
            $attachIds = array_map(function ($slug)
            {
                return slug2id($slug);
            }, $attachTags);
            $pin->tags()->attach($attachIds);
        }

        $pinRepository = new PinRepository();
        $pinRepository->item($pin->slug, true);

        if (!$event->published || $pin->content_type != 1)
        {
            return;
        }

        $flowRepository = new FlowRepository();
        $tagPatchCounter = new TagPatchCounter();
        $pinSlug = $pin->slug;

        if ($event->doPublish)
        {
            foreach ($newTagSlugs as $tagSlug)
            {
                $flowRepository->add_pin($tagSlug, $pinSlug);
                $tagPatchCounter->add($tagSlug, 'pin_count', 1);
            }
        }
        else
        {
            foreach ($detachTags as $tagSlug)
            {
                $flowRepository->del_pin($tagSlug, $pinSlug);
                $tagPatchCounter->add($tagSlug, 'pin_count', -1);
            }

            foreach ($attachTags as $tagSlug)
            {
                $flowRepository->add_pin($tagSlug, $pinSlug);
                $tagPatchCounter->add($tagSlug, 'pin_count', 1);
            }
        }
    }
}
