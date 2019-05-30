<?php

/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/2/6
 * Time: 上午7:36
 */
namespace App\Services\Sms;

use Overtrue\EasySms\EasySms;

class Message
{
    protected $sms;

    public function __construct()
    {
        $this->sms = new EasySms($this->config());
    }

    public function register($phone, $code)
    {
        return $this->send($phone, 'SMS_125015815', [
            'code' => $code
        ]);
    }

    public function bindPhone($phone, $code)
    {
        return $this->send($phone, 'SMS_153885193', [
            'code' => $code
        ]);
    }

    public function forgotPassword($phone, $code)
    {
        return $this->send($phone, 'SMS_125020981', [
            'code' => $code
        ]);
    }

    public function inviteUser($phone, $inviteUser, $newUser)
    {
        return $this->send($phone, 'SMS_157446884', [
            'name' => $inviteUser,
            'time' => $newUser
        ]);
    }

    protected function send($phone, $template, $data)
    {
        try
        {
            $this->sms->send($phone, [
                'template' => $template,
                'data' => $data,
            ]);
            return true;
        }
        catch (\Exception $e)
        {
            app('sentry')->captureException($e);

            return false;
        }
    }

    protected function config()
    {
        return [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,

            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                // 默认可用的发送网关
                'gateways' => [
                    'aliyun'
                ],
            ],
            // 可用的网关配置
            'gateways' => [
                'aliyun' => [
                    'access_key_id' => config('sms.aliyun.id'),
                    'access_key_secret' => config('sms.aliyun.secret'),
                    'sign_name' => config('sms.aliyun.sign'),
                ]
            ],
        ];
    }
}
