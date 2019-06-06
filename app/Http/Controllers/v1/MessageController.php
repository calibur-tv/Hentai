<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\RichContentService;
use App\Http\Repositories\UserRepository;
use App\Models\Message;
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
            ::where('to_user_slug', $user->slug)
            ->orderBy('updated_at', 'DESC')
            ->select('from_user_slug as user', 'type', 'count')
            ->get()
            ->toArray();

        if (empty($menus))
        {
            return $this->resOK([]);
        }

        $userRepository = new UserRepository();

        foreach ($menus as $i => $menu)
        {
            $user = $userRepository->item($menu['user']);
            $menus[$i]['user'] = $user;
            $menus[$i]['channel'] = $menu['type'] . '-' . $user->slug;
        }

        return $this->resOK($menus);
    }

    public function getChatHistory(Request $request)
    {
        $user = $request->user();
        $type = $request->get('message_type');
        $fromUserSlug = $request->get('from_user_slug');
        $sinceId = intval($request->get('since_id'));
        $take = $request->get('take') ?: 15;

        $messages = Message
            ::where('to_user_slug', $user->slug)
            ->where('from_user_slug', $fromUserSlug)
            ->where('type', $type)
            ->when(!$sinceId, function ($query, $sinceId)
            {
                return $query->where('id', '<', $sinceId);
            })
            ->orderBy('created_at', 'DESC')
            ->take($take)
            ->with('content:text')
            ->get()
            ->toArray();

        if (empty($messages))
        {
            return $this->resOK([
                'total' => 0,
                'no_more' => true,
                'result' => []
            ]);
        }

        $userRepository = new UserRepository();
        $richContentService = new RichContentService();

        foreach ($messages as $i => $msg)
        {
            $messages[$i]['user'] = $userRepository->item($msg['from_user_slug']);
            $messages[$i]['content'] = $richContentService->parseRichContent($msg['content']);
        }

        return $this->resOK([
            'total' => 0,
            'no_more' => count($messages) < $take,
            'result' => $messages
        ]);
    }
}
