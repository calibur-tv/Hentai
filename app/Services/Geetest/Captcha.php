<?php

namespace App\Services\Geetest;
/**
 * 极验行为式验证安全平台，php 网站主后台包含的库文件
 *
 * @author Tanxu
 */
class Captcha
{
    const GT_SDK_VERSION = 'php_3.0.0';

    public static $connectTimeout = 1;
    public static $socketTimeout  = 1;

    public function __construct()
    {
        $this->captcha_id  = config('app.geetest.id');
        $this->private_key = config('app.geetest.key');
    }

    /**
     * 根据 Geetest 是否宕机，返回不同的验证码数据
     *
     * @param int $user_id
     * @param string $client_type
     * @param string $ip_address
     * @return array
     */
    public function get($user_id = 0, $client_type = 'unknown', $ip_address = 'unknown')
    {
        $params = [
            'user_id' => $user_id,
            'client_type' => $client_type,
            'ip_address' => $ip_address
        ];

        $data = array_merge([
            'gt' => $this->captcha_id,
            'new_captcha' => 1
        ], $params);
        $query = http_build_query($data);
        $url = "http://api.geetest.com/register.php?" . $query;

        $challenge = $this->send_request($url);
        if (strlen($challenge) !== 32)
        {
            return $this->failback_process();
        }

        return $this->success_process($challenge, $params);
    }

    public function validate(array $args)
    {
        if (!isset($args['geetest_challenge']) || !isset($args['geetest_validate']) || !isset($args['geetest_seccode']) || !isset($args['payload']))
        {
            return false;
        }

        $challenge = $args['geetest_challenge'];
        $validate = $args['geetest_validate'];
        $payload = $args['payload'];

        if (!$args['success'])
        {
            return $this->fail_validate($challenge, $validate, $payload);
        }

        return $this->success_validate($challenge, $validate, $args['geetest_seccode'], $this->decode_payload($payload));
    }

    private function decode_payload($payload)
    {
        return json_decode($payload, true);
    }

    private function encode_payload($params)
    {
        return json_encode($params); // TODO：payload加密
    }

    /**
     * geetest 未宕机
     *
     * @param $challenge
     * @param $params
     * @return array
     */
    private function success_process($challenge, $params)
    {
        $challenge = md5($challenge . $this->private_key);
        $result = array(
            'success'     => 1,
            'gt'          => $this->captcha_id,
            'challenge'   => $challenge,
            'payload'     => $this->encode_payload($params)
        );
        return $result;
    }

    /**
     * Geetest 宕机了
     * @return array
     */
    private function failback_process()
    {
        $rnd1             = md5(rand(0, 100));
        $rnd2             = md5(rand(0, 100));
        $challenge        = $rnd1 . substr($rnd2, 0, 2);
        $result           = array(
            'success'     => 0,
            'gt'          => $this->captcha_id,
            'challenge'   => $challenge,
            'payload'     => (string)time()
        );
        return $result;
    }

    /**
     * 正常模式获取验证结果
     *
     * @param string $challenge
     * @param string $validate
     * @param string $seccode
     * @param array $param
     * @return bool
     */
    public function success_validate($challenge, $validate, $seccode, $param, $json_format = 1)
    {
        if (!$this->check_validate($challenge, $validate))
        {
            return false;
        }

        $query = array(
            "seccode"     => $seccode,
            "timestamp"   => time(),
            "challenge"   => $challenge,
            "captchaid"   => $this->captcha_id,
            "json_format" => $json_format,
            "sdk"         => self::GT_SDK_VERSION
        );


        try
        {
            $query = array_merge($query,$param);
            $url = "http://api.geetest.com/validate.php";
            $codevalidate = $this->post_request($url, $query);
            $obj = json_decode($codevalidate,true);

            if ($obj === false)
            {
                return false;
            }

            return $obj['seccode'] === md5($seccode);
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * 宕机模式获取验证结果
     *
     * @param $challenge
     * @param $validate
     * @param $requestTime
     * @return bool
     */
    public function fail_validate($challenge, $validate, $requestTime)
    {
        if (time() - $requestTime > 60)
        {
            return false;
        }

        return md5($challenge) === $validate;
    }

    /**
     * @param $challenge
     * @param $validate
     * @return bool
     */
    private function check_validate($challenge, $validate)
    {
        if (strlen($validate) !== 32) {
            return false;
        }
        if (md5($this->private_key . 'geetest' . $challenge) !== $validate) {
            return false;
        }

        return true;
    }

    /**
     * GET 请求
     *
     * @param $url
     * @return mixed|string
     */
    private function send_request($url)
    {
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);
            if ($curl_errno > 0) {
                return 0;
            } else {
                return $data;
            }
        } else {
            $opts = array(
                'http' => array(
                    'method'  => "GET",
                    'timeout' => self::$connectTimeout + self::$socketTimeout,
                )
            );
            $context = stream_context_create($opts);
            $data    = @file_get_contents($url, false, $context);
            if ($data) {
                return $data;
            } else {
                return 0;
            }
        }
    }

    /**
     *
     * @param       $url
     * @param array $postdata
     * @return mixed|string
     */
    private function post_request($url, $postdata = '')
    {
        if (!$postdata)
        {
            return false;
        }

        $data = http_build_query($postdata);
        if (function_exists('curl_exec'))
        {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            $data = curl_exec($ch);

            if (curl_errno($ch))
            {
                $err = sprintf("curl[%s] error[%s]", $url, curl_errno($ch) . ':' . curl_error($ch));
                $this->triggerError($err);
            }

            curl_close($ch);
        }
        else
        {
            $opts = array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($data) . "\r\n",
                    'content' => $data,
                    'timeout' => self::$connectTimeout + self::$socketTimeout
                )
            );
            $context = stream_context_create($opts);
            $data    = file_get_contents($url, false, $context);
        }

        return $data;
    }

    /**
     * @param $err
     */
    private function triggerError($err)
    {
        trigger_error($err);
    }
}
