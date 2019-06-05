<?php


namespace App\Http\Modules;


use App\Models\Image;
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
}
