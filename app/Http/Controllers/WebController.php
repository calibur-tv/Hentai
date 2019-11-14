<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-05-10
 * Time: 16:08
 */

namespace App\Http\Controllers;


use App\Http\Repositories\MessageRepository;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\UserRepository;
use App\Models\Tag;
use App\Services\Spider\Query;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebController extends Controller
{
    public function index()
    {
        $query = new Query();
        $result = $query->getBangumiDetail(975);
        return $this->resOK(implode(',', $result['alias']));
    }
}
