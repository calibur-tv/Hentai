<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
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
    }

    /**
     * 偶像详情
     */
    public function show(Request $request)
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
