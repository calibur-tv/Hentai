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
            if ($row['type'] === 'txt')
            {
                $content = Purifier::clean($row['content']);
                while (preg_match('/\n\n\n/', $content))
                {
                    $content = str_replace("\n\n\n", "\n\n", $content);
                }

                $result[] = [
                    'type' => 'txt',
                    'content' => $content
                ];
            }
            else if ($row['type'] === 'img')
            {
                $id = Image::insertGetId($row);
                $result[] = [
                    'type' => 'img',
                    'content' => [
                        'id' => $id,
                        'text' => Purifier::clean($row['text'])
                    ]
                ];
            }
        }

        return json_encode($result);
    }

    public function parseRichContent(string $data)
    {
        $array = json_decode($data, true);
        foreach ($array as $i => $row)
        {
            if ($row['type'] === 'img')
            {
                $image = Image::find($row['content']['id']);

                $array[$i]['content'] = array_merge($row['content'], $image);
            }
        }

        return $array;
    }

    public function detectContentRisk($data)
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

        foreach($data as $row)
        {
            if ($row['type'] === 'txt')
            {
                $filter = $wordsFilter->filter($row['content']);
                $riskWords = array_merge($riskWords, $filter['words']);
                $content[] = [
                    'type' => 'txt',
                    'content' => $filter['text']
                ];
                if ($filter['delete'])
                {
                    $riskScore++;
                }
            }
            else if ($row['type'] === 'img')
            {
                $imageBlock = $row['content'];
                $filter = $wordsFilter->filter($imageBlock['text']);
                $riskWords = array_merge($riskWords, $filter['words']);
                $detect = $imageFilter->check($imageBlock['url']);
                $content[] = [
                    'type' => 'img',
                    'content' => array_merge($imageBlock, [
                        'text' => $filter['words'],
                        'detect' => $detect
                    ])
                ];
                if ($detect['review'] || $detect['delete'])
                {
                    $riskImage[] = $imageBlock['url'];
                }
                if ($detect['delete'])
                {
                    $riskScore++;
                }
                if ($filter['delete'])
                {
                    $riskScore++;
                }
            }
        }

        return [
            'content' => $content,
            'risk_words' => array_unique($riskWords),
            'risk_image' => array_unique($riskImage),
            'risk_score' => $riskScore
        ];
    }
}
