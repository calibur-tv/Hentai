<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-05-10
 * Time: 16:08
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yansongda\LaravelPay\Facades\Pay;

class PayController extends Controller
{
    public function createAlipayOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => [
                'required',
                Rule::in(['app', 'web', 'wap', 'mini']),
            ]
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $order = [
            'out_trade_no' => time(),
            'total_amount' => '0.01',
            'subject' => 'test subject - 测试',
            'http_method' => 'GET'
        ];

        $type = $request->get('type');
        if ($type === 'app') {
            $alipay = Pay::alipay()->app($order);
        } else if ($type === 'web') {
            $alipay = Pay::alipay()->web($order);
        } else if ($type === 'wap') {
            $alipay = Pay::alipay()->wap($order);
        } else if ($type === 'mini') {
            $alipay = Pay::alipay()->mini($order);
        }

        return $this->resOK($alipay->getTargetUrl());
    }

    public function alipayCallback(Request $request)
    {
        return $this->resOK($request->all());
    }
}
