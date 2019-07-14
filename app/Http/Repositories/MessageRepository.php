<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\Message\MessageItemResource;
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

            $messages = MessageItemResource::collection($messages);
            $result = [];
            foreach ($messages as $msg)
            {
                $result[json_encode($msg)] = $msg->id;
            }

            return $result;
        }, ['with_score' => true, 'desc' => false]);

        if (empty($cache))
        {
            return [
                'total' => 0,
                'result' => [],
                'no_more' => true
            ];
        }

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
        $cacheKey = MessageMenu::messageListCacheKey($slug);
        $cache = $this->RedisSort($cacheKey, function () use ($slug)
        {
            $menus = MessageMenu
                ::where('getter_slug', $slug)
                ->orderBy('updated_at', 'DESC')
                ->get();

            $result = [];
            foreach ($menus as $menu)
            {
                $channel = Message::roomCacheKey($menu['type'], $menu['getter_slug'], $menu['sender_slug']);
                $result[$channel] = $menu->generateCacheScore();
            }

            return $result;
        }, ['with_score' => true]);

        if (empty($cache))
        {
            return [];
        }

        $result = [];
        foreach ($cache as $channel => $score)
        {
            $result[] = [
                'channel' => $channel,
                'time' => substr($score, 0, -3),
                'count' => intval(substr($score, -3))
            ];
        }

        return $result;
    }
}
