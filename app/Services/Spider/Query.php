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

            $result = array_map(function ($item)
            {
                return $item['id'];
            }, $result);

            return $result;
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

            $detail = trim($ql->find('.detail')->text());
            $extra['alias'] = [];
            if (isset($extra['简体中文名']))
            {
                $extra['alias'][] = $extra['简体中文名'];
            }
            if (isset($extra['别名']))
            {
                $extra['alias'][] = $extra['别名'];
            }

            return [
                'id' => $id,
                'avatar' => "http:{$avatar}",
                'name' => $extra['简体中文名'],
                'intro' => $detail,
                'alias' => $extra['alias']
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
                    $name = trim($arr[1]);
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

            $intro = trim($ql->find('#subject_summary')->text());

            $tags = $ql->find('.subject_tag_section')->eq(0)->find('span')->map(function ($item){
                return $item->text();
            })->all();

            return [
                'id' => $id,
                'name' => $name,
                'avatar' => "http:{$avatar}",
                'ep_total' => $count,
                'published_at' => $publish,
                'alias' => $alias,
                'intro' => $intro,
                'tags' => $tags
            ];
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
                            $info = $li->find('.nav')->eq(0);
                            return last(explode('/', $info->href));
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
