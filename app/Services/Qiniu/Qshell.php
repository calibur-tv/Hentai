<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-03-14
 * Time: 21:27
 */

namespace App\Services\Qiniu;


use App\Services\Qiniu\Storage\BucketManager;

class Qshell
{
    // 抓取小文件
    public function fetch($srcResUrl, $fileName = '')
    {
        $auth = new \App\Services\Qiniu\Auth();
        $bucketManager = new BucketManager($auth);

        $now = time();
        $str = $this->str_rand();
        $tail = explode('?', $srcResUrl)[0];
        $tail = explode('.', $tail);
        if (count($tail) >= 2)
        {
            $tail = "." . last($tail);
        }
        else
        {
            $tail = '';
        }
        $target = $fileName ? $fileName : "fetch/{$now}/{$str}{$tail}";

        list($ret, $err) = $bucketManager->fetch($srcResUrl, config('app.qiniu.bucket'), $target);

        if ($err !== null)
        {
            return '';
        }

        return $ret['key'];
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
