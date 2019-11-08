<?php


namespace App\Services\Spider;


use QL\QueryList;

class Query
{
    public function fetchMeta($url)
    {
        $ql = QueryList::get($url);
        $title = $ql->find('title')->text();
        $description = $ql->find('meta[name=description]')->content;
        $image = $ql->find('img')->src;

        return [
            'title' => $title,
            'description' => $description,
            'image' => [
                'url' => $image
            ]
        ];
    }

    public function getIdols($url)
    {
        $ql = QueryList::get($url);
        $result = $ql
            ->find('.light_odd')
            ->filter(':has(h2 .tip)')
            ->map(function ($item)
            {
                $avatar = $item->find('a.avatar')->eq(0)->find('img')->src;
                $name = str_replace('/ ', '', $item->find('h2 span')->text());
                $meta = explode(' / ', $item->find('.crt_info .tip')->text());
                $meta = array_map(function ($val)
                {
                    $arr = explode(' ', $val);
                    return count($arr) === 2 ? $arr[1] : '';
                }, $meta);

                return [
                    'avatar' => $avatar,
                    'name' => $name,
                    'sex' => $meta[0] ?? '',
                    'birthday' => $meta[1] ?? ''
                ];
            });

        return $result;
    }
}
