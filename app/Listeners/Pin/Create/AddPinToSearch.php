<?php


namespace App\Listeners\Pin\Create;


use App\Http\Repositories\PinRepository;
use App\Models\Search;

class AddPinToSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Pin\Create $event)
    {
        if (!$event->doPublish || $event->pin->content_type !== 1)
        {
            return;
        }

        $pin = $event->pin;
        $pinRepository = new PinRepository();
        $txtPin = $pinRepository->item($pin->slug);

        $text = $txtPin->title->text . '|' . $txtPin->intro;

        Search::create([
            'type' => 2,
            'slug' => $pin->slug,
            'text' => $text,
            'score' => 0
        ]);
    }
}
