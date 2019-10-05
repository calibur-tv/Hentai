<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\FlowRepository;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FlowController extends Controller
{
    public function pins(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'sort' => [
                'required',
                Rule::in(['newest', 'hottest', 'active']),
            ],
            'time' => 'required',
                Rule::in(['3-day', '7-day', '30-day', 'all']),
            'take' => 'required|integer',
            'is_up' => 'required|integer',
            'spec_id' => 'present|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $slug = $request->get('slug');
        $sort = $request->get('sort');
        $time = $request->get('time');
        $take = $request->get('take');
        $isUp = $request->get('is_up');
        if ($sort === 'newest')
        {
            $specId = $request->get('spec_id');
        }
        else
        {
            $specId = $request->get('spec_id') ? explode(',', $request->get('spec_id')) : [];
        }

        $tagRepository = new TagRepository();
        $tag = $tagRepository->item($slug);
        if (is_null($tag))
        {
            return $this->resOK([
                'result' => [],
                'total' => 0,
                'no_more' => true
            ]);
        }

        $flowRepository = new FlowRepository();
        $idsObj = $flowRepository->pins($slug, $sort, $isUp, $specId, $time, $take);

        if (!$idsObj['total'])
        {
            return $this->resOK($idsObj);
        }

        $pinRepository = new PinRepository();
        $idsObj['result'] = $pinRepository->list($idsObj['result']);

        return $this->resOK($idsObj);
    }

    public function index(Request $request)
    {
        $seenIds = $request->get('seen_ids') ? explode(',', $request->get('seen_ids')) : [];
        $randId = $request->get('rand_id') ?: 1;
        $take = $request->get('take') ?: 10;

        $flowRepository = new FlowRepository();
        $idsObj = $flowRepository->index($seenIds, $randId, $take);

        if (!$idsObj['total'])
        {
            return $this->resOK($idsObj);
        }

        $pinRepository = new PinRepository();
        $idsObj['result'] = $pinRepository->list($idsObj['result']);

        return $this->resOK($idsObj);
    }
}
