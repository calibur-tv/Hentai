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
        self::create([
            'text' => json_encode($data)
        ]);
    }

    public function updateJSON(array $data)
    {
        $extra = $this->pluck('text');
        $extra = json_decode(json_decode($extra)[0], true);

        $this->update([
            'text' => json_encode(array_merge($extra, $data))
        ]);
    }
}
