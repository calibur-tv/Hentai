<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/6/4
 * Time: 上午6:58
 */

namespace App\Services\Trial;


use App\Services\Qiniu\Auth;
use App\Services\Qiniu\Http\Client;

class ImageFilter
{
    public function test($url)
    {
        return $this->validateImage($url);
    }

    public function batchCheck(array $urls)
    {
        $defaultResult = [
            'delete' => false,
            'review' => false
        ];

        foreach ($urls as $url)
        {
            $result = $this->check($url);
            if ($result['delete'])
            {
                $defaultResult['delete'] = true;
            }
            if ($result['review'])
            {
                $defaultResult['review'] = true;
            }
        }

        return $defaultResult;
    }

    public function check($url)
    {
        $defaultResult = [
            'delete' => false,
            'review' => false
        ];

        $response = $this->validateImage($url);
        if (!$response)
        {
            return [
                'delete' => false,
                'review' => true
            ];
        }

        if ($response['suggestion'] === 'pass')
        {
            return $defaultResult;
        }

        $scenes = $response['scenes'];
        if (
            $scenes['ads']['suggestion'] === 'pass' &&
            $scenes['politician']['suggestion'] === 'pass' &&
            $scenes['pulp']['suggestion'] === 'pass' &&
            $scenes['terror']['suggestion'] !== 'pass'
        )
        {
            if (preg_match('/anime/', $scenes['terror']['details']['label']))
            {
                return $defaultResult;
            }
        }

        if ($response['suggestion'] === 'block')
        {
            return [
                'delete' => true,
                'review' => false
            ];
        }

        return [
            'delete' => false,
            'review' => true
        ];
    }

    private function validateImage($src)
    {
        $request_method = 'POST';
        $request_url = 'http://ai.qiniuapi.com/v3/image/censor';
        $content_type = 'application/json';
        $src = patchImage($src);

        $body = json_encode([
            'data' => [
                'uri' => $src
            ],
            'params' => [
                'scenes' => ['pulp', 'terror', 'politician', 'ads']
            ]
        ]);

        $auth = new Auth();
        $authHeaderArray = $auth->authorizationV2($request_url, $request_method, $body, $content_type);

        $header = array(
            "Authorization" => $authHeaderArray['Authorization'],
            "Content-Type" => $content_type,
        );

        try {
            $response = Client::post($request_url, $body, $header);
        } catch (\Exception $e) {
            $response = null;
        }

        if (!$response)
        {
            return null;
        }
        if ($response->statusCode !== 200)
        {
            return null;
        }

        $response = json_decode($response->body, true);
        if ($response['code'] !== 200)
        {
            return null;
        }

        return $response['result'];
    }
}
