<?php

$route->get('/', 'WebController@index');

$route->group(['prefix' => '/callback'], function () use ($route)
{
    $route->group(['prefix' => '/auth'], function () use ($route)
    {
        $route->get('/qq', 'CallbackController@qqAuthRedirect');

        $route->get('/wechat', 'CallbackController@wechatAuthRedirect');

        $route->get('/weixin', 'CallbackController@weixinAuthRedirect');
    });

    $route->group(['prefix' => '/oauth2'], function () use ($route)
    {
        $route->get('/qq', 'CallbackController@qqAuthEntry');

        $route->get('/wechat', 'CallbackController@wechatAuthEntry');

        $route->get('/weixin', 'CallbackController@weixinAuthEntry');
    });
});
