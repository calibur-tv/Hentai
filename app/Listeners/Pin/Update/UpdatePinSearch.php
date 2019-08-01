<?php


namespace App\Listeners\Pin\Update;


use App\Http\Repositories\PinRepository;
use App\Models\Search;

class UpdatePinSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Pin\Update $event)
    {
        if (!$event->published || $event->pin->content_type !== 1)
        {
            return;
        }

        $pin = $event->pin;
        $search = Search
            ::where('type', 2)
            ->where('slug', $pin->slug)
            ->first();

        $pinRepository = new PinRepository();
        $txtPin = $pinRepository->item($pin->slug);

        $text = $txtPin->title->text . '|' . $txtPin->intro;

        if (null === $search)
        {
            Search::create([
                'type' => 2,
                'slug' => $pin->slug,
                'text' => $text
            ]);
        }
        else
        {
            $search->update([
                'text' => $text
            ]);
        }
    }
}
