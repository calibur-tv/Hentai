<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\IdolPatchCounter;
use App\Http\Modules\VirtualCoinService;
use App\Http\Repositories\IdolRepository;
use App\Http\Repositories\UserRepository;
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
        $page = $request->get('page') ?: 1;
        $take = $request->get('take') ?: 10;

        $idolRepository = new IdolRepository();
        $page = $page - 1;
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
        if (empty($idsObj['result']))
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
            $patch['buy_coin_count'] = $info->coin_count;
            $patch['buy_stock_count'] = $info->stock_count;
        }
        else
        {
            $patch['buy_coin_count'] = 0;
            $patch['buy_stock_count'] = 0;
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
        $slug = $request->get('slug');                  // slug
        $coinAmount = $request->get('coin_amount');     // 需要支付的团子数
        $stockCount = $request->get('stock_count');     // 购入的股份数
        $user = $request->user();

        if (!$user)
        {
            return $this->resErrRole('请先登录');
        }

        $idol = Idol
            ::where('slug', $slug)
            ->first();
        if (is_null($idol))
        {
            return $this->resErrNotFound();
        }

        if ($idol->stock_price * $stockCount != $coinAmount)
        {
            return $this->resErrBad('股价已经变更');
        }

        $virtualCoinService = new VirtualCoinService();
        if ($virtualCoinService->hasCoinCount($user) < $coinAmount)
        {
            return $this->resErrBad('没有足够的团子');
        }

        $result = $virtualCoinService->buyIdolStock($user->slug, $slug, $coinAmount);
        if (!$result)
        {
            return $this->resErrServiceUnavailable('交易失败');
        }

        event(new \App\Events\Idol\BuyStock($user, $idol, $coinAmount, $stockCount));

        return $this->resNoContent();
    }

    /**
     * 股势
     */
    public function trend(Request $request)
    {

    }

    public function fans(Request $request)
    {
        $slug = $request->get('slug');
        $page = $request->get('page');
        $take = $request->get('take');
        $idolRepository = new IdolRepository();
        $idol = $idolRepository->item($slug);

        if (is_null($idol))
        {
            return $this->resErrNotFound();
        }

        $idsObj = $idolRepository->idolNewsFans($slug, $page, $take);
        if (empty($idsObj['result']))
        {
            return $this->resOK($idsObj);
        }

        $userRepository = new UserRepository();
        $idsObj['result'] = $userRepository->list($idsObj['result']);

        return $this->resOK($idsObj);
    }

    /**
     * 更新偶像
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->cant('update_idol'))
        {
            return $this->resErrRole();
        }
        $slug = $request->get('slug');
        $title = $request->get('name');
        $alias = $request->get('alias');
        $intro = $request->get('intro');
        $avatar = $request->get('avatar');

        $idolRepository = new IdolRepository();
        $idol = $idolRepository->item($slug);
        if (!$idol)
        {
            return $this->resErrNotFound();
        }

        array_push($alias, $title);
        $alias = array_unique($alias);

        Idol
            ::where('slug', $slug)
            ->update([
                'title' => $title,
                'intro' => $intro,
                'avatar' => $avatar,
                'alias' => implode('|', $alias)
            ]);

        $idolRepository->item($slug, true);

        return $this->resNoContent();
    }
}
