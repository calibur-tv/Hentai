<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-05-10
 * Time: 16:08
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yansongda\LaravelPay\Facades\Pay;

class PayController extends Controller
{
    public function createAlipayOrder(Request $request)
    {
        $order = [
            'out_trade_no' => time(),
            'total_amount' => '0.01',
            'subject' => 'test subject - 测试',
            'http_method' => 'GET'
        ];

        $alipay = Pay::alipay()->web($order);

        return $alipay;
    }

    public function alipayCallback(Request $request)
    {
        return $this->resOK($request->all());
    }
}
