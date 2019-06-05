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
