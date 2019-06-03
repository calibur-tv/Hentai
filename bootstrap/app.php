<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

$app->configure('app');

$app->configure('cors');

$app->configure('permission');

$app->configure('pay');
/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    \Barryvdh\Cors\HandleCors::class,
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'throttle' => App\Http\Middleware\ThrottleMiddleware::class,
    'geetest' => App\Http\Middleware\GeetestMiddleware::class,
    'permission' => Spatie\Permission\Middlewares\PermissionMiddleware::class,
    'role' => Spatie\Permission\Middlewares\RoleMiddleware::class,
]);


$app->alias('cache', \Illuminate\Cache\CacheManager::class);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(Hhxsv5\LaravelS\Illuminate\LaravelSServiceProvider::class);
$app->register(Mews\Purifier\PurifierServiceProvider::class);
$app->register(Overtrue\LaravelFollow\FollowServiceProvider::class);
$app->register(App\Providers\QueryLogServiceProvider::class);
$app->register(Barryvdh\Cors\ServiceProvider::class);
$app->register(Spatie\Permission\PermissionServiceProvider::class);
$app->register(Yansongda\LaravelPay\PayServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers'
], function ($route) {
    require __DIR__.'/../routes/web.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers\v1',
    'prefix' => 'v1'
], function ($route) {
    require __DIR__.'/../routes/route-v1.php';
});

return $app;
