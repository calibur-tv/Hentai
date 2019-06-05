<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositorys\v1\UserRepository;
use App\Models\MessageMenu;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        // 目前使用 socket 发送
    }

    public function getMessageMenu(Request $request)
    {
        $user = $request->user();

        $menus = MessageMenu
            ::where('to_user_id', $user->id)
            ->orderBy('updated_at', 'DESC')
            ->select('from_user_id as user', 'type', 'count')
            ->get()
            ->toArray();

        if (empty($menus))
        {
            return $this->resOK([]);
        }

        $userRepository = new UserRepository();

        foreach ($menus as $i => $menu)
        {
            $user = $userRepository->itemById($menu['user']);
            $menus[$i]['user'] = $user;
            $menus[$i]['channel'] = $menu['type'] . '-' . $user->slug;
        }

        return $this->resOK($menus);
    }

    public function getChatHistory(Request $request)
    {

    }
}
