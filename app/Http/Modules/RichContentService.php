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
            else if ($type === 'linkTool')
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
            else if ($type === 'linkTool')
            {
                $words .= $row['data']['link'];
                $words .= $row['data']['meta']['title'];
                $words .= $row['data']['meta']['description'];
            }
            if ($type === 'list')
            {
                foreach ($row['data']['items'] as $item)
                {
                    $words .= $item;
                }
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
