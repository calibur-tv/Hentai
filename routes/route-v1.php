<?php

$route->group(['prefix' => '/door'], function () use ($route)
{
    $route->post('/message', 'DoorController@sendMessage');

    $route->post('/register', 'DoorController@register');

    $route->post('/login', 'DoorController@login');

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->post('/get_user_info', 'DoorController@getUserInfo');

        $route->post('/logout', 'DoorController@logout');
    });

    $route->post('/bind_phone', 'DoorController@bindPhone');

    $route->post('/wechat_mini_app_login', 'DoorController@wechatMiniAppLogin');

    $route->post('/wechat_mini_app_get_token', 'DoorController@wechatMiniAppToken');

    $route->post('/reset_password', 'DoorController@resetPassword');

    $route->group(['prefix' => '/oauth2'], function () use ($route)
    {
        $route->post('/qq', 'DoorController@qqAuthRedirect');

        $route->post('/wechat', 'DoorController@wechatAuthRedirect');
    });
});

$route->group(['prefix' => 'user'], function () use ($route)
{
    $route->post('send_message', 'UserController@send_message');

    $route->post('sign_in', 'UserController@sign_in');

    $route->post('sign_up', 'UserController@sign_up');

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->post('sign_out', 'UserController@sign_out');

        // $route->post('update_info', 'UserController@update_info');
    });

    // $route->post('reset_password', 'UserController@reset_password');

    $route->group(['middleware' => 'admin'], function () use ($route)
    {
        // $route->get('trials', 'UserController@trials');

        // $route->post('resolve', 'UserController@resolve');

        // $route->post('reject', 'UserController@reject');
    });
});

$route->group(['prefix' => 'image'], function () use ($route)
{
    $route->get('captcha', 'ImageController@captcha');

    $route->get('uptoken', 'ImageController@uptoken');
});

$route->group(['prefix' => 'pin'], function () use ($route)
{
    $route->get('show_info', 'PinController@show_info');

    $route->get('show_meta', 'PinController@show_meta');

    $route->group(['middleware' => ['auth', 'throttle']], function () use ($route)
    {
        $route->post('create', 'PinController@create');

        $route->post('toggle_tag', 'PinController@toggle_tag');

        $route->post('update', 'PinController@update');

        $route->post('destroy', 'PinController@destroy');

        $route->post('report', 'PinController@report');

        $route->post('mark', 'PinController@mark');

        $route->post('vote', 'PinController@vote');

        $route->post('reward', 'PinController@reward');

        $route->post('share', 'PinController@share');

        $route->post('view', 'PinController@view');
    });

    $route->group(['middleware' => 'admin'], function () use ($route)
    {
        $route->get('trials', 'PinController@trials');

        $route->post('resolve', 'PinController@resolve');

        $route->post('reject', 'PinController@reject');
    });
});

$route->group(['prefix' => 'tag'], function () use ($route)
{
    $route->get('show', 'TagController@show');

    $route->post('create', 'TagController@create');

    $route->post('update', 'TagController@update');

    $route->post('delete', 'TagController@delete');

    $route->post('combine', 'TagController@combine');

    $route->post('relink', 'TagController@relink');

//    $route->group(['middleware' => ['auth', 'throttle']], function () use ($route)
//    {
//        $route->post('create', 'TagController@create');
//    });
//
//    $route->group(['middleware' => 'admin'], function () use ($route)
//    {
//        $route->post('update', 'TagController@update');
//
//        $route->post('destroy', 'TagController@destroy');
//
//        $route->post('combine', 'TagController@combine');
//
//        $route->post('redirect', 'TagController@redirect');
//    });
});

$route->group(['prefix' => 'comment'], function () use ($route)
{
    $route->get('main_item', 'CommentController@main_item');

    $route->get('main_list', 'CommentController@main_list');

    $route->get('reply_list', 'CommentController@reply_list');

    $route->group(['middleware' => ['auth', 'throttle']], function () use ($route)
    {
        $route->post('create', 'CommentController@create');

        $route->post('reply', 'CommentController@reply');

        $route->post('destroy', 'CommentController@destroy');

        $route->post('vote', 'CommentController@vote');
    });

    $route->group(['middleware' => 'admin'], function () use ($route)
    {
        $route->get('trials', 'PinController@trials');

        $route->post('resolve', 'PinController@resolve');

        $route->post('reject', 'PinController@reject');
    });
});

$route->group(['prefix' => 'flow'], function () use ($route)
{
    $route->get('recommended', 'FlowController@recommended');

    $route->group(['prefix' => 'hottest'], function () use ($route)
    {
        $route->get('weekly', 'FlowController@weekly');

        $route->get('daily', 'FlowController@daily');

        $route->get('monthly', 'FlowController@monthly');
    });

    $route->get('newest', 'FlowController@newest');

    $route->get('category', 'FlowController@category');

    $route->get('users', 'FlowController@users');
});

$route->group(['prefix' => 'console'], function () use ($route)
{
    $route->group(['prefix' => 'role'], function () use ($route)
    {
        $route->get('show_all_roles', 'RoleController@showAllRoles');

        $route->get('show_all_users', 'RoleController@getUsersByCondition');

        $route->post('create_role', 'RoleController@createRole');

        $route->post('create_permission', 'RoleController@createPermission');

        $route->post('toggle_permission_to_role', 'RoleController@togglePermissionToRole');

        $route->post('toggle_role_to_user', 'RoleController@toggleRoleToUser');
    });
});
