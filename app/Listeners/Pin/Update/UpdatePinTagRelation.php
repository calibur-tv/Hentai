<?php

namespace App\Listeners\Pin\Update;

use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatePinTagRelation
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
        $tagRepository = new TagRepository();
        $pinRepository = new PinRepository();
        $oldPin = $pinRepository->item($pin->slug);
        $newTag = $event->tags;

        if ($event->doPublish || !$event->published)
        {
            $oldAreaSlug = $oldPin->area ? $oldPin->area->slug : '';
            if ($newTag['area'] != $oldAreaSlug)
            {
                $newArea = $tagRepository->item($newTag['area']);
                if ($newArea)
                {
                    if ($oldAreaSlug)
                    {
                        $pin->tags()->detach(slug2id($oldAreaSlug));
                    }
                    $pin->tags()->attach(slug2id($newTag['area']));
                }
            }

            $oldTopicSlug = $oldPin->topic ? $oldPin->topic->slug : '';
            if ($newTag['topic'] != $oldTopicSlug)
            {
                $newTopic = $tagRepository->item($newTag['topic']);
                if ($newTopic)
                {
                    if ($oldTopicSlug)
                    {
                        $pin->tags()->detach(slug2id($oldTopicSlug));
                    }
                    $pin->tags()->attach(slug2id($newTag['topic']));
                }
            }
        }

        $oldNotebookSlug = $oldPin->notebook ? $oldPin->notebook->slug : '';
        if ($newTag['notebook'] != $oldNotebookSlug)
        {
            $newNotebook = $tagRepository->item($newTag['notebook']);
            if ($newNotebook)
            {
                if ($oldNotebookSlug)
                {
                    $pin->tags()->detach(slug2id($oldNotebookSlug));
                }
                $pin->tags()->attach(slug2id($newTag['notebook']));
            }

            $tagRepository->bookmarks($event->user->slug, true);
        }
    }
}
