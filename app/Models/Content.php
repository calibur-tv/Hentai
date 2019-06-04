<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contentable_id',
        'contentable_type',
        'text'
    ];

    public function contentable()
    {
        return $this->morphTo();
    }

    public static function createJSON(array $data)
    {
        return self::create([
            'text' => json_encode($data)
        ]);
    }

    public static function createRich(array $data)
    {
        $result = [];
        foreach ($data as $row)
        {
            if ($row['type'] === 'txt')
            {
                $result[] = [
                    'type' => 'txt',
                    'text' => $row['content']
                ];
            }
            else if ($row['type'] === 'img')
            {
                $id = Image::insertGetId($row);
                $result[] = [
                    'type' => 'img',
                    'content' => [
                        'id' => $id,
                        'text' => $row['text']
                    ]
                ];
            }
        }

        return self::create([
            'text' => self::rich2string($data)
        ]);
    }

    public function updateJSON(array $data)
    {
        $extra = $this->pluck('text');
        $extra = json_decode(json_decode($extra)[0], true);

        return $this->update([
            'text' => json_encode(array_merge($extra, $data))
        ]);
    }

    protected function rich2string(array $data)
    {
        $result = [];
        foreach ($data as $row)
        {
            if ($row['type'] === 'txt')
            {
                $content = $row['content'];
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
                        'text' => $row['text']
                    ]
                ];
            }
        }

        return json_encode($result);
    }

    protected function string2rich(string $data)
    {
        $array = json_decode(json_decode($data), true);
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
