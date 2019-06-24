<?php


namespace App\Http\Modules;


use App\Models\Image;
use App\Services\Trial\ImageFilter;
use App\Services\Trial\WordsFilter;
use Mews\Purifier\Facades\Purifier;

class RichContentService
{
    public function saveRichContent(array $data)
    {
        $result = [];
        foreach ($data as $row)
        {
            $type = $row['type'];
            if ($type === 'paragraph')
            {
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'text' => Purifier::clean($row['data']['text'])
                    ]
                ];
            }
            else if ($type === 'header')
            {
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'level' => $row['data']['level'],
                        'text' => Purifier::clean($row['data']['text'])
                    ]
                ];
            }
            else if ($type === 'image')
            {
                $result[] = [
                    'type' => $type,
                    'data' => array_merge(
                        $row['data'],
                        ['caption' => Purifier::clean($row['data']['caption'])]
                    )
                ];
            }
            else if ($type === 'title')
            {
                $result[] = [
                    'type' => $type,
                    'data' => array_merge(
                        $row['data'],
                        ['text' => Purifier::clean($row['data']['text'])]
                    )
                ];
            }
            else if ($type === 'link')
            {
                $meta = $row['data']['meta'];

                $result[] = [
                    'type' => $type,
                    'data' => [
                        'link' => $row['data']['link'],
                        'meta' => [
                            'title' => Purifier::clean($meta['title']),
                            'description' => Purifier::clean($meta['description']),
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
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'style' => $row['data']['style'],
                        'items' => array_map(function ($item)
                        {
                            return Purifier::clean($item);
                        }, $row['data']['items'])
                    ]
                ];
            }
            else if ($type === 'checklist')
            {
                $result[] = [
                    'type' => $type,
                    'data' => [
                        'items' => array_map(function ($item)
                        {
                            return [
                                'text' => Purifier::clean($item['text']),
                                'checked' => $item['checked']
                            ];
                        }, $row['data']['items'])
                    ]
                ];
            }
            else if ($type === 'embed')
            {
                $result[] = [
                    'type' => $type,
                    'data' => array_merge($row['data'], [
                        'caption' => Purifier::clean($row['data']['caption'])
                    ])
                ];
            }
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function parseRichContent(string $data)
    {
        return json_decode($data, true);
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
                $words .= $row['data']['meta']['title'];
                $words .= $row['data']['meta']['description'];
            }
            else if ($type === 'list')
            {
                foreach ($row['data']['items'] as $item)
                {
                    $words .= $item;
                }
            }
            else if ($type === 'checklist')
            {
                foreach ($row['data']['items'] as $item)
                {
                    $words .= $item['text'];
                }
            }
            else if ($type === 'embed')
            {
                $words .= $item['data']['caption'];
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

            if ($withImage)
            {
                foreach ($image as $url)
                {
                    $detect = $imageFilter->check($url);
                    if ($detect['review'] || $detect['delete'])
                    {
                        $riskImage[] = $imageBlock['url'];
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
            // TODO 把触发的敏感词记录到一个地方，查看是不是误杀
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
