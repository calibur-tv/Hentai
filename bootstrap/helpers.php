<?php

if ( ! function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

function slug2id($slug)
{
    return floor(base_convert($slug, 36, 10) / 1000);
}

function id2slug($id)
{
    return base_convert(($id * 1000 + rand(0, 999)), 10, 36);
}

function trimImage($url)
{
    $arr = explode('calibur.tv/', $url);
    return count($arr) === 1 ? $url : explode('calibur.tv/', $url)[1];
}

function patchImage($url, $default = '')
{
    if (preg_match('/http/', $url))
    {
        return $url;
    }

    return config('app.image-cdn')[array_rand(config('app.image-cdn'))]. ($url ?: $default);
}

function str_rand($length = 8, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
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

function daily_cache_expire()
{
    return strtotime(date('Y-m-d'), time()) + 86400 + rand(3600, 10800);
}
