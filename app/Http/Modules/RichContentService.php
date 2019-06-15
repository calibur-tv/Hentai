<?php


namespace App\Http\Modules;


use App\Models\Image;
use App\Services\Trial\ImageFilter;
use App\Services\Trial\WordsFilter;
use Mews\Purifier\Facades\Purifier;

class RichContentService
{
    /**
     * 格式：
     * txt => type, content
     * img => type, content[id, text]
     */

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
                $id = Image::insertGetId($row['content']);
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
        $result = [];
        foreach ($array as $row)
        {
            if ($row['type'] === 'img')
            {
                $image = Image::find($row['content']['id'])->toArray();
                if (is_null($image))
                {
                    continue;
                }

                $result[] = [
                    'type' => 'img',
                    'content' => array_merge($row['content'], $image)
                ];
            }
            else if ($row['type'] == 'txt')
            {
                $result[] = [
                    'type' => 'txt',
                    'content' => $row['content']
                ];
            }
        }

        return $result;
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
                if (!$withImage)
                {
                    continue;
                }
                $imageBlock = $row['content'];
                $filter = $wordsFilter->filter($row['text']);
                $riskWords = array_merge($riskWords, $filter['words']);
                if (isset($imageBlock['id']))
                {
                    $imageUrl = Image::where('id', $imageBlock['id'])->pluck('url')->first();
                }
                else
                {
                    $imageUrl = $imageBlock['url'];
                }
                $detect = $imageFilter->check($imageUrl);
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
