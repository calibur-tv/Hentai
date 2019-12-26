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

    $route->post('/qq_mini_app_login', 'DoorController@qqMiniAppLogin');

    $route->post('/qq_mini_app_get_token', 'DoorController@qqMiniAppToken');

    $route->post('/reset_password', 'DoorController@resetPassword');

    $route->group(['prefix' => '/oauth2'], function () use ($route)
    {
        $route->get('/ticket', 'DoorController@shareTicket');

        $route->post('/qq', 'DoorController@qqAuthRedirect');

        $route->post('/wechat', 'DoorController@wechatAuthRedirect');
    });
});

$route->group(['prefix' => 'user'], function () use ($route)
{
    $route->get('show', 'UserController@show');

    $route->get('relation', 'UserController@getUserRelation');

    $route->get('timeline', 'UserController@timeline');

    $route->get('managers', 'UserController@managers');

    $route->get('idols', 'UserController@idols');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('detect_relation', 'UserController@detectUserRelation');

        $route->get('patch', 'UserController@patch');
    });

    $route->get('batch_patch', 'UserController@batchPatch');

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->post('update_info', 'UserController@updateProfile');

        $route->post('daily_sign', 'UserController@dailySign');

        $route->get('roles', 'UserController@getRoles');

        $route->post('add_manager', 'UserController@addManager');

        $route->post('remove_manager', 'UserController@removeManager');
    });
});

$route->group(['prefix' => 'search'], function () use ($route)
{
    $route->get('mixin', 'SearchController@mixin');

    $route->get('tags', 'TagController@search');
});

$route->group(['prefix' => 'bangumi'], function () use ($route)
{
    $route->get('show', 'BangumiController@show');

    $route->get('rank', 'BangumiController@rank');

    $route->get('atfield', 'BangumiController@atfield');

    $route->get('idols', 'BangumiController@idols');

    $route->get('relation', 'BangumiController@relation');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('patch', 'BangumiController@patch');

        $route->get('fetch', 'BangumiController@fetch');
    });

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->post('create', 'BangumiController@create');
    });

    $route->group(['prefix' => 'update', 'middleware' => 'user'], function () use ($route)
    {
        $route->post('profile', 'BangumiController@updateProfile');

        $route->post('set_parent', 'BangumiController@updateAsParent');

        $route->post('set_child', 'BangumiController@updateAsChild');

        $route->post('fetch_idols', 'BangumiController@fetchIdols');
    });
});

$route->group(['prefix' => 'join', 'middleware' => 'auth'], function () use ($route)
{
    $route->post('create', 'JoinController@create');

    $route->post('recommend', 'JoinController@recommend');

    $route->post('delete', 'JoinController@delete');

    $route->post('begin', 'JoinController@begin');

    $route->get('list', 'JoinController@list');

    $route->post('submit', 'JoinController@submit');

    $route->post('vote', 'JoinController@vote');

    $route->get('result', 'JoinController@result');

    $route->get('flow', 'JoinController@flow');

    $route->group(['prefix' => 'rule'], function () use ($route)
    {
        $route->get('show', 'JoinController@getJoinRule');

        $route->post('update', 'JoinController@updateJoinRule');
    });
});

$route->group(['prefix' => 'idol'], function () use ($route)
{
    $route->get('list', 'IdolController@list');

    $route->get('show', 'IdolController@show');

    $route->get('fans', 'IdolController@fans');

    $route->get('trend', 'IdolController@trend');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('patch', 'IdolController@patch');

        $route->post('vote', 'IdolController@vote');

        $route->post('update', 'IdolController@update');

        $route->get('fetch', 'IdolController@fetch');
    });

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->post('create', 'IdolController@create');
    });

    $route->get('batch_patch', 'IdolController@batchPatch');
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

$route->group(['prefix' => 'social', 'middleware' => 'auth'], function () use ($route)
{
    $route->post('toggle', 'ToggleController@toggle');

    $route->post('vote', 'ToggleController@vote');
});

$route->group(['prefix' => 'pin'], function () use ($route)
{
    $route->get('show', 'PinController@show');

    $route->get('marked_tag', 'PinController@getMarkedTag');

    $route->get('vote_stat', 'PinController@voteStat');

    $route->get('timeline', 'PinController@timeline');

    $route->get('batch_patch', 'PinController@batchPatch');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('patch', 'PinController@patch');
    });

    $route->group(['middleware' => ['auth']], function () use ($route)
    {
        $route->post('create/story', 'PinController@createStory');

        $route->get('update/content', 'PinController@getEditableContent');

        $route->post('update/story', 'PinController@updateStory');

        $route->post('delete', 'PinController@deletePin');

        $route->post('move', 'PinController@movePin');

        $route->get('drafts', 'PinController@userDrafts');
    });

    $route->group(['middleware' => 'auth'], function () use ($route)
    {
        $route->get('trials', 'PinController@trials');

        $route->post('resolve', 'PinController@resolve');

        $route->post('reject', 'PinController@reject');
    });

    $route->group(['prefix' => 'editor'], function () use ($route)
    {
        $route->get('fetch_site', 'PinController@fetchSiteMeta');
    });
});

$route->group(['prefix' => 'atfield', 'middleware' => 'auth'], function () use ($route)
{
    $route->post('create', 'ATFieldController@create');

    $route->post('recommend', 'ATFieldController@recommend');

    $route->post('delete', 'ATFieldController@delete');

    $route->post('begin', 'ATFieldController@begin');

    $route->post('invite', 'ATFieldController@invite');

    $route->post('change_master', 'ATFieldController@changeMaster');

    $route->get('list', 'ATFieldController@list');

    $route->post('submit', 'ATFieldController@submit');

    $route->get('result', 'ATFieldController@result');

    $route->get('flow', 'ATFieldController@flow');

    $route->group(['prefix' => 'rule'], function () use ($route)
    {
        $route->get('show', 'ATFieldController@getJoinRule');

        $route->post('update', 'ATFieldController@updateJoinRule');
    });
});

$route->group(['prefix' => 'tag'], function () use ($route)
{
    $route->get('show', 'TagController@show');

    $route->get('atfield', 'TagController@atfield');

    $route->get('hottest', 'TagController@hottest');

    $route->get('children', 'TagController@children');

    $route->group(['middleware' => 'user'], function () use ($route)
    {
        $route->get('patch', 'TagController@patch');
    });

    $route->get('batch_patch', 'TagController@batchPatch');

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
    $route->get('pins', 'FlowController@pins');

    $route->get('index', 'FlowController@index');
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

            $route->get('blocked', 'TrialController@getBlockedWords');

            $route->post('test', 'TrialController@textTest');

            $route->post('add', 'TrialController@addWords');

            $route->post('delete', 'TrialController@deleteWords');

            $route->post('clear', 'TrialController@clearBlockedWords');
        });

        $route->post('image/test', 'TrialController@imageTest');
    });
});
