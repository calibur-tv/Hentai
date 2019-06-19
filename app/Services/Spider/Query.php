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
}
