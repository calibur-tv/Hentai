<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\BangumiPatchCounter;
use App\Http\Repositories\BangumiRepository;
use App\Http\Repositories\IdolRepository;
use App\Models\Bangumi;
use App\Models\Search;
use App\Services\Spider\BangumiSource;
use App\Services\Spider\Query;
use Illuminate\Http\Request;

class BangumiController extends Controller
{
    public function show(Request $request)
    {
        $slug = $request->get('slug');
        if (!$slug)
        {
            return $this->resErrBad();
        }

        $bangumiRepository = new BangumiRepository();

        $bangumi = $bangumiRepository->item($slug);
        if (!$bangumi)
        {
            return $this->resErrNotFound();
        }

        return $this->resOK($bangumi);
    }

    public function patch(Request $request)
    {
        $slug = $request->get('slug');

        $bangumiRepository = new BangumiRepository();
        $data = $bangumiRepository->item($slug);
        if (is_null($data))
        {
            return $this->resErrNotFound();
        }

        $bangumiPatchCounter = new BangumiPatchCounter();
        $patch = $bangumiPatchCounter->all($slug);
        $user = $request->user();

        if (!$user)
        {
            return $this->resOK($patch);
        }

        $bangumiId = slug2id($slug);
        $patch['is_liked'] = $user->hasLiked($bangumiId, Bangumi::class);

        return $this->resOK($patch);
    }

    public function rank(Request $request)
    {
        $page = $request->get('page') ?: 0;
        $take = $request->get('take') ?: 20;

        $bangumiRepository = new BangumiRepository();
        $idsObj = $bangumiRepository->rank($page, $take);

        if (empty($idsObj['result']))
        {
            return $this->resOK($idsObj);
        }

        $idsObj['result'] = $bangumiRepository->list($idsObj['result']);

        return $this->resOK($idsObj);
    }

    public function score(Request $request)
    {

    }

    public function relation(Request $request)
    {
        $slug = $request->get('slug');

        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($slug);
        if (!$bangumi)
        {
            return $this->resErrNotFound();
        }

        $result = [
            'parent' => null,
            'children' => []
        ];

        if ($bangumi->is_parent)
        {
            $childrenSlug = Bangumi
                ::where('parent_slug', $bangumi->slug)
                ->pluck('slug')
                ->toArray();

            $result['children'] = $bangumiRepository->list($childrenSlug);
        }

        if ($bangumi->parent_slug)
        {
            $result['parent'] = $bangumiRepository->item($bangumi->parent_slug);
        }

        return $this->resOK($result);
    }

    public function idols(Request $request)
    {
        $slug = $request->get('slug');
        $page = $request->get('page') ?: 0;
        $take = $request->get('take') ?: 20;

        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($slug);
        if (!$bangumi)
        {
            return $this->resErrNotFound();
        }

        $idsObj = $bangumiRepository->idol_slugs($slug, $page, $take);
        if (empty($idsObj['result']))
        {
            return $this->resOK($idsObj);
        }

        $idolRepository = new IdolRepository();

        $idsObj['result'] = $idolRepository->list($idsObj['result']);

        return $this->resOK($idsObj);
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

    public function fetchIdols(Request $request)
    {
        $slug = $request->get('slug');
        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($slug);

        if (!$bangumi)
        {
            return $this->resErrNotFound();
        }

        $bangumiSource = new BangumiSource();
        $bangumiSource->moveBangumiIdol($bangumi->slug, $bangumi->source_id);
        $bangumiRepository->idol_slugs($slug, 0, 0, true);

        return $this->resNoContent();
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if ($user->cant('update_bangumi'))
        {
            return $this->resErrRole();
        }

        $avatar = $request->get('avatar');
        $title = $request->get('name');
        $alias = $request->get('alias');
        $intro = $request->get('intro');
        $slug = $request->get('slug');

        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($slug);
        if (!$bangumi)
        {
            return $this->resErrNotFound();
        }

        array_push($alias, $title);
        $alias = implode('|', array_unique($alias));

        Bangumi
            ::where('slug', $slug)
            ->update([
                'avatar' => $avatar,
                'title' => $title,
                'intro' => $intro,
                'alias' => $alias
            ]);

        Search
            ::where('slug', $slug)
            ->where('type', 4)
            ->update([
                'alias' => str_replace('|', ',', $alias)
            ]);

        $bangumiRepository->item($slug, true);

        return $this->resNoContent();
    }

    public function updateAsParent(Request $request)
    {
        $user = $request->user();
        if ($user->cant('update_bangumi'))
        {
            return $this->resErrRole();
        }
        $bangumiSlug = $request->get('bangumi_slug');
        $bangumi = Bangumi
            ::where('slug', $bangumiSlug)
            ->first();

        $bangumi->update([
            'is_parent' => true
        ]);

        return $this->resNoContent();
    }

    public function updateAsChild(Request $request)
    {
        $user = $request->user();
        if ($user->cant('update_bangumi'))
        {
            return $this->resErrRole();
        }
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
