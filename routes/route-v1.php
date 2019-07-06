<?php

$route->group(['prefix' => 'door'], function () use ($route)
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
    $route->get('show', 'UserController@show');

    $route->get('relation', 'UserController@getUserRelation');

    $route->get('timeline', 'UserController@timeline');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('detect_relation', 'UserController@detectUserRelation');

        $route->get('patch', 'UserController@patch');
    });

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->post('update_info', 'UserController@updateProfile');

        $route->post('daily_sign', 'UserController@dailySign');
    });
});

$route->group(['prefix' => 'message'], function () use ($route)
{
    $route->get('total', 'MessageController@getMessageTotal');

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->post('send', 'MessageController@sendMessage');

        $route->get('menu', 'MessageController@getMessageMenu');

        $route->get('history', 'MessageController@getChatHistory');

        $route->get('get_channel', 'MessageController@getMessageChannel');

        $route->post('delete_channel', 'MessageController@deleteMessageChannel');

        $route->post('clear_channel', 'MessageController@clearMessageChannel');
    });
});

$route->group(['prefix' => 'image'], function () use ($route)
{
    $route->get('captcha', 'ImageController@captcha');

    $route->get('uptoken', 'ImageController@uptoken');
});

$route->group(['prefix' => 'social'], function () use ($route)
{
    $route->post('toggle', 'ToggleController@toggle');
});

$route->group(['prefix' => 'pin'], function () use ($route)
{
    $route->get('show', 'PinController@show');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('patch', 'PinController@patch');
    });

    $route->group(['prefix' => 'update', 'middleware' => ['auth']], function () use ($route)
    {
        $route->get('content', 'PinController@getEditableContent');

        $route->post('story', 'PinController@updateStory');
    });

    $route->group(['middleware' => ['auth', 'throttle']], function () use ($route)
    {
        $route->group(['prefix' => 'create'], function () use ($route)
        {
            $route->post('story', 'PinController@createStory');
        });

        $route->post('delete', 'PinController@deletePin');

        $route->post('report', 'PinController@report');

        $route->post('mark', 'PinController@mark');

        $route->post('vote', 'PinController@vote');

        $route->post('reward', 'PinController@reward');

        $route->post('share', 'PinController@share');
    });

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->get('drafts', 'PinController@userDrafts');

        $route->get('trials', 'PinController@trials');

        $route->post('resolve', 'PinController@resolve');

        $route->post('reject', 'PinController@reject');
    });

    $route->group(['prefix' => 'editor'], function () use ($route)
    {
        $route->get('fetch_site', 'PinController@fetchSiteMeta');
    });
});

$route->group(['prefix' => 'tag'], function () use ($route)
{
    $route->get('show', 'TagController@show');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('patch', 'TagController@patch');
    });

    $route->get('mixin_patch', 'TagController@mixinPatch');

    $route->get('bookmarks', 'TagController@bookmarks');

    $route->group(['middleware' => ['auth']], function () use ($route)
    {
        $route->post('create', 'TagController@create');

        $route->post('update', 'TagController@update');

        $route->post('delete', 'TagController@delete');

        $route->post('combine', 'TagController@combine');

        $route->post('relink', 'TagController@relink');
    });
});

$route->group(['prefix' => 'comment'], function () use ($route)
{
    $route->get('show', 'CommentController@show');

    $route->get('list', 'CommentController@list');

    $route->get('talk', 'CommentController@talk');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('patch', 'CommentController@patch');
    });

    $route->group(['middleware' => ['auth']], function () use ($route)
    {
        $route->post('create', 'CommentController@create');

        $route->post('delete', 'CommentController@delete');
    });

    $route->group(['middleware' => 'auth'], function () use ($route)
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

$route->group(['prefix' => 'console', 'middleware' => 'auth'], function () use ($route)
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

    $route->group(['prefix' => 'trial'], function () use ($route)
    {
        $route->group(['prefix' => 'words'], function () use ($route)
        {
            $route->get('show', 'TrialController@showWords');

            $route->get('test', 'TrialController@textTest');

            $route->post('add', 'TrialController@addWords');

            $route->post('delete', 'TrialController@deleteWords');
        });

        $route->get('image/test', 'TrialController@imageTest');
    });
});
