<?php

namespace App\Providers;

use App\User;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request)
        {
            $token = $this->parseAuthHeader($request);
            if (!$token)
            {
                return null;
            }

            try
            {
                $maskId = explode(':', $token)[0];
                if (strlen($maskId) > 11)
                {
                    return null;
                }
                $userId = slug2id($maskId);

                return User::where('id', $userId)->first();
            }
            catch (\Exception $e)
            {
                return null;
            }
        });
    }

    protected function parseAuthHeader($request, $header = 'authorization', $prefix = 'bearer')
    {
        $token = $request->headers->get($header);

        if (!starts_with(strtolower($token), $prefix))
        {
            return '';
        }

        return trim(str_ireplace($prefix, '', $token));
    }
}
