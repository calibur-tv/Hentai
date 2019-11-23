<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019/3/2
 * Time: 2:58 PM
 */

namespace App\Services;


use Illuminate\Support\Facades\Log;

class WXBizDataCrypt
{
    private $appid;
    private $sessionKey;

    private static $OK = 0;
    private static $IllegalAesKey = -41001;
    private static $IllegalIv = -41002;
    private static $IllegalBuffer = -41003;
    private static $DecodeBase64Error = -41004;

    /**
     * 构造函数
     * @param $sessionKey string 用户在小程序登录后获取的会话密钥
     * @param $appid string 小程序的appid
     */
    public function __construct($appid, $sessionKey)
    {
        $this->sessionKey = $sessionKey;
        $this->appid = $appid;
    }


    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($encryptedData, $iv, &$data)
    {
        if (strlen($this->sessionKey) != 24) {
            return $this::$IllegalAesKey;
        }
        $aesKey=base64_decode($this->sessionKey);


        if (strlen($iv) != 24) {
            return $this::$IllegalIv;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result );
        if( $dataObj  == NULL )
        {
            return $this::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return $this::$IllegalBuffer;
        }
        $data = $result;
        return $this::$OK;
    }
}
