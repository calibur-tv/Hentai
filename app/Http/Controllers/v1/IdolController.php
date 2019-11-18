<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\IdolPatchCounter;
use App\Http\Modules\VirtualCoinService;
use App\Http\Repositories\IdolRepository;
use App\Models\Idol;
use App\Models\IdolFans;
use Illuminate\Http\Request;

class IdolController extends Controller
{
    /**
     * 偶像列表
     */
    public function list(Request $request)
    {
        $sort = $request->get('sort');
        $page = $request->get('page');
        $take = $request->get('take') ?: 10;

        $idolRepository = new IdolRepository();
        if ($sort === 'active')
        {
            $idsObj = $idolRepository->idolActiveIds($page, $take);
        }
        else if ($sort === 'release')
        {
            $idsObj = $idolRepository->idolReleaseIds($page, $take);
        }
        else
        {
            $idsObj = $idolRepository->idolHotIds($page, $take);
        }
        if (!$idsObj['total'])
        {
            return $this->resOK($idsObj);
        }

        $idsObj['result'] = $idolRepository->list($idsObj['result']);

        return $this->resOK($idsObj);
    }

    /**
     * 偶像详情
     */
    public function show(Request $request)
    {
        $slug = $request->get('slug');
        $idolRepository = new IdolRepository();

        $result = $idolRepository->item($slug);
        if (!$result)
        {
            return $this->resErrNotFound();
        }

        return $this->resOK($result);
    }

    public function patch(Request $request)
    {
        $slug = $request->get('slug');
        $idolRepository = new IdolRepository();
        $idol = $idolRepository->item($slug);

        if (!$idol)
        {
            return $this->resErrNotFound();
        }

        $idolPathCounter = new IdolPatchCounter();
        $patch = $idolPathCounter->all($slug);

        $user = $request->user();
        $info = null;
        if ($user)
        {
            $info = IdolFans
                ::where('idol_slug', $slug)
                ->where('user_slug', $user->slug)
                ->first();
        }

        if ($info)
        {
            $patch['star_count'] = $info->star_count;
            $patch['total_price'] = $info->total_price;
        }
        else
        {
            $patch['star_count'] = 0;
            $patch['total_price'] = 0;
        }

        return $this->resOK($patch);
    }

    public function batchPatch(Request $request)
    {
        $list = $request->get('slug') ? explode(',', $request->get('slug')) : [];
        $idolPatchCounter = new IdolPatchCounter();

        $result = [];
        foreach ($list as $slug)
        {
            $result[$slug] = $idolPatchCounter->all($slug);
        }

        return $this->resOK($result);
    }

    /**
     * 入股
     */
    public function vote(Request $request)
    {
        $slug = $request->get('slug');      // slug
        $amount = $request->get('amount');  // 需要支付的团子数
        $count = $request->get('count');    // 购入的股份数

        $idol = Idol
            ::where('slug', $slug)
            ->first();
        if (!$idol)
        {
            return $this->resErrNotFound();
        }

        if ($idol->stock_price * $count != $amount)
        {
            return $this->resErrBad('股价已经变更');
        }

        $user = $request->user();
        $virtualCoinService = new VirtualCoinService();
        if ($virtualCoinService->hasCoinCount($user) < $amount)
        {
            return $this->resErrBad('没有足够的团子');
        }

        $result = $virtualCoinService->buyIdolStock($user->slug, $slug, $amount);
        if (!$result)
        {
            return $this->resErrServiceUnavailable('交易失败');
        }

        event(new \App\Events\Idol\BuyStock($user, $idol, $amount, $count));

        return $this->resNoContent();
    }

    /**
     * 股势
     */
    public function trend(Request $request)
    {

    }

    /**
     * 更新偶像
     */
    public function update(Request $request)
    {

    }
}
