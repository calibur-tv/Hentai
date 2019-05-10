<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-03-14
 * Time: 21:27
 */

namespace App\Services\Qiniu;


class Qshell
{
    // 抓取大文件
    public function sync($srcResUrl, $fileName = '')
    {
        $now = time();
        $str = $this->str_rand();
        $target = $fileName ? $fileName : "user/qshell/upload/video/{$now}-{$str}.mp4";
        $commends = [
            "qshell sync {$srcResUrl} video -k {$target}"
        ];

        foreach ($commends as $script)
        {
            exec($script);
        }

        return $target;
    }

    // 抓取小文件
    public function fetch($srcResUrl, $fileName = '')
    {
        $now = time();
        $str = $this->str_rand();
        $tail = explode('?', $srcResUrl)[0];
        $tail = explode('.', $tail);
        if (count($tail) == 2)
        {
            $tail = ".{$tail[1]}";
        }
        else
        {
            $tail = '';
        }
        $target = $fileName ? $fileName : "user/qshell/upload/image/{$now}-{$str}{$tail}";
        $commends = [
            "qshell fetch {$srcResUrl} clannader -k {$target}"
        ];

        if (config('app.env') !== 'production')
        {
            $commends = [$commends[1]];
        }

        foreach ($commends as $script)
        {
            exec($script);
        }

        return $target;
    }

    private function str_rand($length = 8, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        if (!is_int($length) || $length < 0) {
            return false;
        }

        $string = '';
        for ($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }
        return $string;
    }
}