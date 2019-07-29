<?php


namespace App\Http\Modules\Counter;


use App\Http\Modules\RichContentService;
use App\Http\Repositories\PinRepository;
use App\Models\PinAnswer;

class PinVoteCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('pin_answers', false);
    }

    public function boot($slug)
    {
        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($slug);
        if (is_null($pin))
        {
            return [];
        }

        $richContentService = new RichContentService();
        $vote = $richContentService->getFirstType($pin->content, 'vote');
        if (is_null($vote))
        {
            return [];
        }

        $result = [
            'right_total' => 0
        ];
        foreach ($vote['items'] as $ans)
        {
            $result[$ans['id']] = 0;
        }

        $answers = PinAnswer
            ::where('pin_slug', $slug)
            ->select('selected_uuid', 'is_right')
            ->get()
            ->toArray();

        foreach ($answers as $ans)
        {
            $selected = json_decode($ans['selected_uuid'], true);
            if ($ans['is_right'] == 1)
            {
                $result['right_total']++;
            }
            foreach ($selected as $hash)
            {
                isset($result[$hash]) ? $result[$hash]++ : $result[$hash] = 0;
            }
        }

        return $result;
    }
}
