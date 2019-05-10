<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;

class FlowController extends Controller
{
    /**
     * 推荐列表，支持多个分类
     * 瀑布流，按照推荐的时间做创建时间来计算热度
     * seen_ids
     */
    public function recommended()
    {
        return $this->resOK(Purifier::clean('<<>>/<p>123</p><br><div style="font-size: 12px">66</div>&lt;script&gt;alert(123)&lt;/script&gt;'));
    }

    /**
     * 热门列表，支持多个分类（一日、三日、一周...?）
     * Twitter 信息流，按照创建时间来计算热度
     * seen_ids
     */
    public function hottest()
    {

    }

    /**
     * 最新的内容，按照创建时间排序，Twitter 信息流
     * last_id
     */
    public function newest()
    {

    }

    /**
     * 按照 tag 来选择，提供最新，热门，推荐
     * 只有推荐的是瀑布流，其它的都是 Twitter 信息流
     * 最新用 last_id，其余用 seen_ids
     */
    public function category()
    {

    }

    /**
     * 用户的内容，Twitter 信息流
     * page
     */
    public function users()
    {

    }
}
