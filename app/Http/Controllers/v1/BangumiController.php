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

    public function score(Request $request)
    {

    }

    public function create(Request $request)
    {
        $sourceId = $request->get('source_id');
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
            return $this->resErrThrottle('数据爬取失败');
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
        $bangumiSlug = $request->get('bangumi_slug');
        $bangumi = Bangumi
            ::where('slug', $bangumiSlug)
            ->first();

        $bangumi->update([
            'is_parent' => false
        ]);

        return $this->resNoContent();
    }

    public function updateAsChild(Request $request)
    {
        $parentSlug = $request->get('parent_slug');
        $childSlug = $request->get('child_slug');

        $parent = Bangumi
            ::where('slug', $parentSlug)
            ->first();

        if (!$parent || !$parent->is_parent)
        {
            return $this->resErrBad('指定节点非合集');
        }

        $child = Bangumi
            ::where('slug', $childSlug)
            ->first();

        if (!$child)
        {
            return $this->resErrBad();
        }

        $child->update([
            'parent_slug' => $parent->slug
        ]);

        return $this->resNoContent();
    }
}
