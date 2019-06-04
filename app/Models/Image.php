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
        return array_search($mineStr, $this->mimeArr);
    }

    public function getMimeAttribute($mimeInt)
    {
        return array_search($mimeInt, array_flip($this->mimeArr));
    }

    public function setUrlAttribute($url)
    {
        $arr = explode('calibur.tv/', $url);

        return count($arr) === 1 ? $url : explode('calibur.tv/', $url)[1];
    }

    public function getUrlAttribute($url)
    {
        if (preg_match('/http/', $url))
        {
            return $url;
        }

        return config('app.image-cdn')[array_rand(config('app.image-cdn'))] . $url;
    }
}
