<?php

$route->get('/', 'WebController@index');

$route->get('/sentry-debug', 'WebController@sentry');

$route->group(['prefix' => '/callback'], function () use ($route)
{
    $route->group(['prefix' => '/alipay'], function () use ($route)
    {
        $route->get('/create_order', 'PayController@createAlipayOrder');

        $route->get('/pay_v1', 'PayController@alipayCallback');
    });

    $route->group(['prefix' => '/auth'], function () use ($route)
    {
        $route->get('/qq', 'AuthController@qqAuthRedirect');

        $route->get('/wechat', 'AuthController@wechatAuthRedirect');

        $route->get('/weixin', 'AuthController@weixinAuthRedirect');
    });

    $route->group(['prefix' => '/oauth2'], function () use ($route)
    {
        $route->get('/qq', 'AuthController@qqAuthEntry');

        $route->get('/wechat', 'AuthController@wechatAuthEntry');

        $route->get('/weixin', 'AuthController@weixinAuthEntry');
    });
});
