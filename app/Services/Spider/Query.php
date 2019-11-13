<?php


namespace App\Services\Spider;


use Illuminate\Support\Facades\Log;
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

    public function getBangumiIdols($id)
    {
        try
        {
            $url = "http://bgm.tv/subject/{$id}/characters";
            $ql = QueryList::get($url);
            $result = $ql
                ->find('.light_odd')
                ->filter(':has(h2 .tip)')
                ->map(function ($item)
                {
                    $id = last(explode('/', $item->find('a.avatar')->eq(0)->href));
                    $name = str_replace('/ ', '', $item->find('h2 span')->text());
                    $meta = explode(' / ', $item->find('.crt_info .tip')->text());
                    $meta = array_map(function ($val)
                    {
                        $arr = explode(' ', $val);
                        return count($arr) === 2 ? $arr[1] : '';
                    }, $meta);

                    return [
                        'id' => $id,
                        'name' => $name,
                        'sex' => $meta[0] ?? '',
                        'birthday' => $meta[1] ?? ''
                    ];
                })
                ->all();

            $result = array_filter($result, function ($item)
            {
                return (isset($item['sex']) && $item['sex']) && (isset($item['birthday']) && $item['birthday']);
            });

            $filtered = [];
            foreach ($result as $i => $item)
            {
                $detail = $this->getIdolDetail($item['id']);
                if ($detail)
                {
                    $filtered[] = array_merge($item, $detail);
                }
            }

            return $filtered;
        }
        catch (\Exception $e)
        {
            Log::info("[--spider--]：get bangumi - idol {$id} failed");
            return [];
        }
    }

    public function getBangumiList($page)
    {
        try
        {
            $url = "http://bgm.tv/anime/browser?sort=rank&page={$page}";
            $ql = QueryList::get($url);

            $ids = $ql
                ->find('#browserItemList')
                ->children()
                ->map(function ($item)
                {
                    return last(explode('/', $item->find('.subjectCover')->eq(0)->href));
                })
                ->all();

            $result = [];

            foreach ($ids as $id)
            {
                $result[] = $this->getBangumiDetail($id);
            }

            $result = array_filter($result, function ($item)
            {
                return !!$item;
            });

            return $result;
        }
        catch (\Exception $e)
        {
            Log::info("[--spider--]：get bangumi page {$page} failed");
            return [];
        }
    }

    public function getIdolDetail($id)
    {
        try
        {
            $url = "http://bgm.tv/character/{$id}";
            $ql = QueryList::get($url);

            $avatar = $ql->find('.infobox')->eq(0)->find('img')->eq(0)->src;
            $meta = explode(PHP_EOL, $ql->find('#infobox')->text());
            $extra = [];
            foreach ($meta as $item)
            {
                $arr = explode(': ', $item);
                $extra[$arr[0]] = $arr[1];
            }

            $detail = $ql->find('.detail')->text();

            return [
                'avatar' => "http:{$avatar}",
                'detail' => $detail,
                'extra' => $extra
            ];
        }
        catch (\Exception $e)
        {
            Log::info("[--spider--]：get idol {$id} failed");
            return null;
        }
    }

    public function getBangumiDetail($id)
    {
        try
        {
            $url = "http://bgm.tv/subject/{$id}";
            $ql = QueryList::get($url);

            $avatar = $ql
                ->find('.infobox')
                ->eq(0)
                ->find('img')
                ->eq(0)
                ->src;

            $meta = explode(PHP_EOL, $ql->find('#infobox')->text());
            $name = '';
            $count = 0;
            $publish = '';
            $alias = [];
            foreach ($meta as $item)
            {
                $arr = explode(': ', $item);
                if ($arr[0] === '中文名')
                {
                    $name = $arr[1];
                    $alias[] = $name;
                }
                else if ($arr[0] === '话数')
                {
                    $count = $arr[1];
                }
                else if ($arr[0] === '放送开始')
                {
                    $publish = $arr[1];
                }
                else if ($arr[0] === '别名')
                {
                    $alias[] = $arr[1];
                }
            }

            $detail = $ql->find('#subject_summary')->text();

            $tags = $ql->find('.subject_tag_section')->eq(0)->find('span')->map(function ($item){
                return $item->text();
            })->all();

            $result = [
                'id' => $id,
                'name' => $name,
                'avatar' => "http:{$avatar}",
                'ep_total' => $count,
                'published_at' => $publish,
                'alias' => $alias,
                'detail' => $detail,
                'tags' => $tags
            ];

            return $result;
        }
        catch (\Exception $e)
        {
            Log::info("[--spider--]：get bangumi {$id} failed");
            return null;
        }
    }

    public function getNewsBangumi()
    {
        try
        {
            $url = 'http://bangumi.tv/calendar';
            $ql = QueryList::get($url);
            $data = $ql
                ->find('.coverList')
                ->map(function ($item)
                {
                    return $item
                        ->find('li')
                        ->map(function ($li)
                        {
                            $info = $li->find('.nav')->eq(1);
                            return [
                                'id' => last(explode('/', $info->href)),
                                'name' => $info->text()
                            ];
                        })
                        ->all();
                })
                ->all();

            $result = [];

            foreach ($data as $row)
            {
                $result = array_merge($result, $row);
            }

            return $result;
        }
        catch (\Exception $e)
        {
            Log::info("[--spider--]：get news bangumi failed");
            return [];
        }
    }
}
