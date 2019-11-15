<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Bangumi;
use App\Services\Spider\BangumiSource;
use App\Services\Spider\Query;
use Illuminate\Http\Request;

class BangumiController extends Controller
{
    public function show(Request $request)
    {

    }

    public function rank(Request $request)
    {

    }

    public function create(Request $request)
    {
        $sourceId = $request->get('id');
        $hasBangumi = Bangumi
            ::where('source_id', $sourceId)
            ->first();

        if ($hasBangumi)
        {
            return $this->resOK($hasBangumi);
        }

        $query = new Query();
        $info = $query->getBangumiDetail($sourceId);

        if (is_null($info))
        {
            return $this->resErrThrottle('数据爬去失败');
        }

        $bangumiSource = new BangumiSource();
        $bangumi = $bangumiSource->importBangumi($info);

        if (is_null($bangumi))
        {
            return $this->resErrServiceUnavailable();
        }

        return $this->resOK($bangumi);
    }

    public function updateProfile(Request $request)
    {

    }

    public function updateAsParent(Request $request)
    {

    }

    public function updateAsChild(Request $request)
    {

    }
}
