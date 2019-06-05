<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-09
 * Time: 21:51
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'url',
        'width',
        'height',
        'size',
        'mime',
    ];

    protected $mimeArr = [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'image/gif'
    ];

    public function setMimeAttribute($mineStr)
    {
        $this->attributes['mime'] = array_search($mineStr, $this->mimeArr);
    }

    public function getMimeAttribute($mimeInt)
    {
        return array_search($mimeInt, array_flip($this->mimeArr));
    }

    public function setUrlAttribute($url)
    {
        $this->attributes['url'] = trimImage($url);
    }

    public function getUrlAttribute($url)
    {
        return patchImage($url);
    }
}
