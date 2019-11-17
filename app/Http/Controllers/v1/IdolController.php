<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\IdolRepository;
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

    }

    /**
     * 入股
     */
    public function vote(Request $request)
    {

    }

    /**
     * 股势
     */
    public function trend(Request $request)
    {

    }

    /**
     * 创建偶像
     */
    public function create(Request $request)
    {

    }

    /**
     * 更新偶像
     */
    public function update(Request $request)
    {

    }
}
