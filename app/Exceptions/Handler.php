<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Exceptions\HttpResponseException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof HttpResponseException)
        {
            return $e->getResponse();
        }
        else if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException)
        {
            return response([
                'code' => 404,
                'message' => '您访问的资源不存在'
            ], 404);
        }
        else if ($e instanceof AuthorizationException)
        {
            return response([
                'code' => 403,
                'message' => '用户认证错误'
            ], 403);
        }
        else if ($e instanceof ValidationException && $e->getResponse())
        {
            return $e->getResponse();
        }

        return response([
            'code' => 503,
            'message' => '系统升级暂不可用'
        ], 503);
    }
}
