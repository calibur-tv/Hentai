<?php


namespace App\Http\Modules;


use App\Services\Trial\ImageFilter;
use App\Services\Trial\WordsFilter;
use Illuminate\Support\Facades\Redis;
use Mews\Purifier\Facades\Purifier;

class RichContentService
{
    public function preFormatContent(array $data)
    {
        $result = [];
        foreach ($data as $row)
        {
            if ($row['type'] === 'vote')
            {
                $result[] = [
                    'type' => 'vote',
                    'data' => $this->formatVote(
                        $row['data']['items'],
                        $row['data']['right_ids'],
                        $row['data']['max_select'],
                        $row['data']['expired_at']
                    )
                ];
            }
            else
            {
                $result[] = $row;
            }
        }

        return $result;
    }

    public function saveRichContent(array $data)
    {
        $result = [];
        foreach ($data as $row)
        {
            $type = $row['type'];
            if ($type === 'paragraph')
            {
                $text = trim($row['data']['text']);
                if (!$text)
                {
                    continue;
                }
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'text' => Purifier::clean($text)
                    ]
                ];
            }
            else if ($type === 'header')
            {
                $text = trim($row['data']['text']);
                if (!$text)
                {
                    continue;
                }
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'level' => $row['data']['level'],
                        'text' => Purifier::clean($text)
                    ]
                ];
            }
            else if ($type === 'image')
            {
                $text = trim($row['data']['caption']);
                $result[] = [
                    'type' => $type,
                    'data' => array_merge(
                        $row['data'],
                        ['caption' => $text ? Purifier::clean($text) : '']
                    )
                ];
            }
            else if ($type === 'title')
            {
                $text = trim($row['data']['text']);
                if (!$text && (!isset($row['data']['banner']) || !$row['data']['banner']))
                {
                    continue;
                }
                $result[] = [
                    'type' => $type,
                    'data' => array_merge(
                        $row['data'],
                        ['text' => $text ? Purifier::clean($text) : '']
                    )
                ];
            }
            else if ($type === 'link')
            {
                $meta = $row['data']['meta'];
                $title = trim($meta['title']);
                $description = trim($meta['description']);

                $result[] = [
                    'type' => $type,
                    'data' => [
                        'link' => $row['data']['link'],
                        'meta' => [
                            'title' => Purifier::clean($title),
                            'description' => Purifier::clean($description),
                            'image' => $meta['image']
                        ]
                    ]
                ];
            }
            else if ($type === 'delimiter')
            {
                $result[] = $row;
            }
            else if ($type === 'list')
            {
                $items = array_filter($row['data']['items'], function ($item)
                {
                    return !!trim($item);
                });
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'style' => $row['data']['style'],
                        'items' => array_map(function ($item)
                        {
                            return Purifier::clean(trim($item));
                        }, $items)
                    ]
                ];
            }
            else if ($type === 'vote')
            {
                $items = array_filter($row['data']['items'], function ($item)
                {
                    return !!trim($item['text']);
                });
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'right_ids' => $row['data']['right_ids'],
                        'max_select' => $row['data']['max_select'],
                        'expired_at' => $row['data']['expired_at'],
                        'items' => array_map(function ($item)
                        {
                            return [
                                'text' => Purifier::clean(trim($item['text'])),
                                'id' => $item['id']
                            ];
                        }, $items)
                    ]
                ];
            }
            else if ($type === 'checklist')
            {
                $items = array_filter($row['data']['items'], function ($item)
                {
                    return !!trim($item['text']);
                });
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'items' => array_map(function ($item)
                        {
                            return [
                                'text' => Purifier::clean(trim($item['text'])),
                                'checked' => $item['checked']
                            ];
                        }, $items)
                    ]
                ];
            }
            else if ($type === 'video')
            {
                if (!preg_match('/https?:\/\/(www|m)\.bilibili\.com/', $row['data']['source']))
                {
                    continue;
                }
                $text = trim($row['data']['caption']);
                $result[] = [
                    'type' => $type,
                    'data' => array_merge($row['data'], [
                        'caption' => $text ? Purifier::clean($text) : ''
                    ])
                ];
            }
            else if ($type === 'music')
            {
                if (!preg_match('/https?:\/\/music\.163\.com/', $row['data']['source']))
                {
                    continue;
                }
                $text = trim($row['data']['caption']);
                $result[] = [
                    'type' => $type,
                    'data' => array_merge($row['data'], [
                        'caption' => $text ? Purifier::clean($text) : ''
                    ])
                ];
            }
            else if ($type === 'baidu')
            {
                $url = trim($row['data']['url']);
                if (!preg_match('/https?:\/\/pan\.baidu\.com/', $url))
                {
                    continue;
                }
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'url' => $url,
                        'password' => trim($row['data']['password']),
                        'visit_type' => $row['data']['visit_type']
                    ]
                ];
            }
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function parseRichContent(string $data)
    {
        return json_decode($data, true);
        $data = json_decode($data, true);
        $result = [];
        foreach ($data as $row)
        {
            if ($row['type'] === 'vote')
            {
                // 过滤掉答案
                unset($row['data']['right_ids']);
                $result[] = [
                    'type' => 'vote',
                    'data' => $row['data']
                ];
            }
            else
            {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function getFirstType($content, $type)
    {
        $array = gettype($content) === 'array' ? json_decode(json_encode($content), true) : json_decode($content, true);
        $result = null;
        foreach ($array as $row)
        {
            if ($row['type'] === $type)
            {
                $result = $row['data'];
                break;
            }
        }

        return $result;
    }

    public function formatVote(array $answers, $right_index, int $max_select = 1, int $expired_at = 0)
    {
        $items = [];
        $ids = [];
        foreach ($answers as $i => $ans)
        {
            $id = $i . str_rand();
            $items[] = [
                'id' => $id,
                'text' => $ans
            ];
            $ids[] = $id;
        }

        $rights = [];
        if (gettype($right_index) !== 'array')
        {
            $rights[] = $ids[$right_index];
        }
        else
        {
            foreach ($right_index as $index)
            {
                $rights[] = $ids[$index];
            }
        }

        if ($max_select < 1)
        {
            $max_select = 1;
        }
        else if ($max_select >= count($items))
        {
            $max_select = count($items) - 1;
        }

        return [
            'items' => $items,
            'right_ids' => $rights,
            'expired_at' => $expired_at,
            'max_select' => $max_select
        ];
    }

    public function paresPureContent(array $data)
    {
        $result = '';
        foreach ($data as $row)
        {
            $type = $row['type'];
            if ($type === 'paragraph')
            {
                $result .= $row['data']['text'];
            }
            else if ($type === 'header')
            {
                $result .= $row['data']['text'];
            }
            else if ($type === 'list')
            {
                foreach ($row['data']['items'] as $i => $item)
                {
                    $result .= (($i + 1) . ' ' . $item);
                }
            }
            else if ($type === 'checklist')
            {
                foreach ($row['data']['items'] as $i => $item)
                {
                    $result .= (($i + 1) . ' ' . $item['text']);
                }
            }
            else if ($type === 'vote')
            {
                foreach ($row['data']['items'] as $i => $item)
                {
                    $result .= (($i + 1) . ' ' . $item['text']);
                }
            }
            else if ($type === 'image')
            {
                $result .= $row['data']['caption'];
            }
            else if ($type === 'video')
            {
                $result .= $row['data']['caption'];
            }
            else if ($type === 'music')
            {
                $result .= $row['data']['caption'];
            }
        }

        return $result;
    }

    public function parseRichPoster($title, array $data)
    {
        $imageCount = 0;
        $videoCount = 0;
        $musicCount = 0;
        $firstImage = null;
        $firstVideo = null;
        $firstMusic = null;
        $images = [];

        foreach ($data as $row)
        {
            $type = $row['type'];
            if ($type === 'image')
            {
                $imageCount++;
                if (!$firstImage)
                {
                    $firstImage = $row;
                }
                $images[] = $row['data']['file'];
            }
            else if ($type === 'video')
            {
                $videoCount++;
                if (!$firstVideo)
                {
                    $firstVideo = $row;
                }
            }
            else if ($type === 'music')
            {
                $musicCount++;
                if (!$firstMusic)
                {
                    $firstMusic = $row;
                }
            }
        }

        if (
            !$firstImage &&
            !$firstVideo &&
            !$firstMusic &&
            !isset($title['banner'])
        )
        {
            return null;
        }

        $banner = null;
        if (isset($title['banner']))
        {
            $banner = $title['banner'];
            $imageCount++;
            array_unshift($images, $banner);
        }
        else if ($firstImage)
        {
            $banner = $firstImage['data']['file'];
        }

        return [
            'image_count' => $imageCount,
            'video_count' => $videoCount,
            'music_count' => $musicCount,
            'first_image' => $firstImage,
            'first_video' => $firstVideo,
            'first_music' => $firstMusic,
            'banner' => $banner,
            'images' => array_slice($images, 0, 3)
        ];
    }

    public function detectContentRisk($data, $withImage = true)
    {
        if (gettype($data) === 'string')
        {
            $data = $this->parseRichContent($data);
        }

        $wordsFilter = new WordsFilter();
        $imageFilter = new ImageFilter();

        $content = [];
        $riskWords = [];
        $riskImage = [];
        $riskScore = 0;
        $useReview = 0;

        $image = [];
        $words = '';

        foreach($data as $row)
        {
            $type = $row['type'];
            if ($type === 'paragraph')
            {
                $words .= $row['data']['text'];
            }
            else if ($type === 'header')
            {
                $words .= $row['data']['text'];
            }
            else if ($type === 'image')
            {
                $words .= $row['data']['caption'];
                $image[] = $row['data']['file']['url'];
            }
            else if ($type === 'title')
            {
                $words .= $row['data']['text'];
                if (isset($row['data']['banner']))
                {
                    $image[] = $row['data']['banner']['url'];
                }
            }
            else if ($type === 'link')
            {
                $words .= $row['data']['link'];
                if (isset($row['data']['meta']))
                {
                    $words .= $row['data']['meta']['title'] ?? '';
                    $words .= $row['data']['meta']['description'] ?? '';
                }
            }
            else if ($type === 'list')
            {
                foreach ($row['data']['items'] as $item)
                {
                    $words .= $item;
                }
            }
            else if ($type === 'vote')
            {
                foreach ($row['data']['items'] as $item)
                {
                    $words .= $item['text'];
                }
            }
            else if ($type === 'checklist')
            {
                foreach ($row['data']['items'] as $item)
                {
                    $words .= $item['text'];
                }
            }
            else if ($type === 'video')
            {
                $words .= $row['data']['caption'];
            }
            else if ($type === 'music')
            {
                $words .= $row['data']['caption'];
            }

            if ($words)
            {
                $filter = $wordsFilter->filter($words);
                $riskWords = $filter['words'];
                if ($filter['delete'])
                {
                    $riskScore++;
                }
                if ($filter['review'])
                {
                    $useReview++;
                }
            }

            if ($withImage && config('app.env') !== 'local')
            {
                foreach ($image as $url)
                {
                    $detect = $imageFilter->check($url);
                    if ($detect['review'] || $detect['delete'])
                    {
                        $riskImage[] = $url;
                    }
                    if ($detect['delete'])
                    {
                        $riskScore++;
                    }
                    if ($detect['review'])
                    {
                        $useReview++;
                    }
                }
            }
        }

        if ($riskScore > 0)
        {
            Redis::RPUSH('blocked-risk-words', $riskWords);
        }

        return [
            'content' => $content,
            'risk_words' => array_unique($riskWords),
            'risk_image' => array_unique($riskImage),
            'risk_score' => $riskScore,
            'use_review' => $useReview
        ];
    }
}
