<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Modules\RichContentService;
use App\Models\Message;
use App\Models\MessageMenu;

class MessageRepository extends Repository
{
    public function history($type, $getterSlug, $senderSlug, $sinceId, $isUp, $count)
    {
        $cacheKey = Message::roomCacheKey($type, $getterSlug, $senderSlug);
        $cache = $this->RedisSort($cacheKey, function () use ($type, $getterSlug, $senderSlug)
        {
            $messages = Message
                ::where('type', $type)
                ->whereRaw('getter_slug = ? and sender_slug = ?', [$senderSlug, $getterSlug])
                ->orWhereRaw('getter_slug = ? and sender_slug = ?', [$getterSlug, $senderSlug])
                ->orderBy('created_at', 'ASC')
                ->with(['content', 'sender'])
                ->get();

            if (empty($messages))
            {
                return [];
            }

            $richContentService = new RichContentService();
            $result = [];
            foreach ($messages as $msg)
            {
                $data = [
                    'user' => [
                        'slug' => $msg->sender->slug,
                        'avatar' => $msg->sender->avatar,
                        'nickname' => $msg->sender->nickname,
                    ],
                    'content' => $richContentService->parseRichContent($msg->content->text),
                    'created_at' => $msg->created_at
                ];

                $result[json_encode($data)] = $msg->id;
            }

            return $result;
        }, ['with_score' => true, 'desc' => false]);

        $format = $this->filterIdsByMaxId(array_flip($cache), $sinceId, $count, true, $isUp);
        $result = [];
        foreach ($format['result'] as $id => $item)
        {
            $result[] = array_merge(json_decode($item, true), ['id' => $id]);
        }

        $format['result'] = $result;

        return $format;
    }

    public function menu($slug)
    {
        $cacheKey = MessageMenu::cacheKey($slug);
        $cache = $this->RedisSort($cacheKey, function () use ($slug)
        {
            $menus = MessageMenu
                ::where('getter_slug', $slug)
                ->orderBy('updated_at', 'DESC')
                ->get();

            $result = [];
            foreach ($menus as $menu)
            {
                $key = $menu['type'] . '#' . $menu['sender_slug'];
                $val = $menu->generateCacheScore();
                $result[$key] = $val;
            }

            return $result;
        }, ['with_score' => true]);

        if (empty($cache))
        {
            return [];
        }

        $result = [];
        foreach ($cache as $key => $value)
        {
            $arr1 = explode('#', $key);
            $result[] = [
                'channel' => $key,
                'type' => $arr1[0],
                'slug' => $arr1[1],
                'time' => substr($value, 0, -3),
                'count' => intval(substr($value, -3))
            ];
        }

        return $result;
    }
}
