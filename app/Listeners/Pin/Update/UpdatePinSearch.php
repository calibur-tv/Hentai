<?php


namespace App\Listeners\Pin\Update;


use App\Http\Repositories\PinRepository;
use App\Models\Search;
use Illuminate\Support\Facades\Log;

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
        $score =
            $pin->like_count +
            $pin->mark_count +
            $pin->reward_count +
            $pin->comment_count +
            $pin->visit_count;

        if (null === $search)
        {
            Search::create([
                'type' => 2,
                'slug' => $pin->slug,
                'text' => $text,
                'score' => $score
            ]);
        }
        else
        {
            $search->update([
                'text' => $text,
                'score' => $score
            ]);
        }
    }
}
