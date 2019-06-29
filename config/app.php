<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Hentai'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Asia/Shanghai',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => env('APP_LOCALE', 'zh-CN'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'md5' => env('MD5_SALT'),

    'cipher' => 'AES-256-CBC',

    'tag' => [
        'bangumi' => '2he',
        'game' => '285',
        'topic' => '3p6',
        'newbie' => 'ugf6',             // 话题 -> 新手乐园
        'trash' => 'fa0',               // 垃圾箱
        'notebook' => 'uh4f',           // 用户专栏
        'pin' => '1o2bqt',              // 文章标签
    ],

    'qiniu' => [
        'driver'     => 'qiniu',
        'access_key' => env('QINIU_ACCESS_KEY'),
        'secret_key' => env('QINIU_SECRET_KEY'),
        'bucket'     => env('QINIU_BUCKET')
    ],

    'image-cdn' => [
        'https://m1.calibur.tv/'
    ],

    'geetest' => [
        'id' => env('GEETEST_ID'),
        'key' => env('GEETEST_KEY')
    ],

    'aliyun' => [
        'sms' => [
            'access_key_id' => env('ALIYUN_SMS_ID'),
            'access_key_secret' => env('ALIYUN_SMS_SECRET'),
            'sign_name' => '上海十六夜'
        ]
    ],

    'oauth2' => [
        // QQ 登录
        'qq' => [
            'client_id' => env('QQ_AUTH_APP_ID'),
            'client_secret' => env('QQ_AUTH_APP_KEY'),
            'redirect' => 'https://api.calibur.tv/callback/auth/qq'
        ],
        // PC 微信登录
        'wechat' => [
            'client_id' => env('WECHAT_APP_OPEN_ID'),
            'client_secret' => env('WECHAT_APP_OPEN_SECRET'),
            'redirect' => 'https://api.calibur.tv/callback/auth/wechat'
        ],
        // H5 微信登录
        'weixin' => [
            'client_id' => env('WECHAT_APP_OWNER_ID'),
            'client_secret' => env('WECHAT_APP_OWNER_SECRET'),
            'redirect' => 'https://api.calibur.tv/callback/auth/weixin'
        ],
        // 微信小程序
        'wechat_mini_app' => [
            'app_id' => env('WECHAT_MINI_APP_ID'),
            'app_secret' => env('WECHAT_MINI_APP_SECRET')
        ]
    ]
];
