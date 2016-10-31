<?php
namespace mycompany\common;

use Yii;
use yii\redis\Cache;
use yii\redis\Connection;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/9/21
 * Time: 下午6:46
 */
class WeiXinSDK
{
    private $appId;
    private $appSecret;
    public $appMchId = '';
    public $appPayKey = '';
    public $certFile = '';
    public $certKey = '';

    public function __construct($appId, $appSecret, $mchId = '', $paykey = '', $cert_file = '', $key_file = '')
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->appMchId = $mchId;
        $this->appPayKey = $paykey;
        $this->certFile = $cert_file;
        $this->certKey = $key_file;
    }

    public function getSignPackage($url)
    {
        //$url = "http://h5.cheshi.hangjiashuo.com/#/home";
        //$url = Yii::app()->params['web_host'].'/api'.$_SERVER['REQUEST_URI'];
        $jsapiTicket = $this->getJsApiTicket();
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = [
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        ];
        return $signPackage;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket()
    {
        $ticket = Yii::$app->redis->get('wx_ticket');
        if (!$ticket) {
            $accessToken = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                Yii::$app->redis->setex('wx_ticket', 7000, $ticket);
            }
        }
        return $ticket;
    }

    private function getAccessToken()
    {
        //$access_token = Yii::app()->redis8->getClient()->get('wx_token');
        $access_token = Yii::$app->redis8->get('wx_token');
        if (!$access_token) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                Yii::$app->redis->setex('wx_token', 7000, $access_token);
            }
        }
        return $access_token;
    }

    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function makeSign($values)
    {
        ksort($values);
        $buff = "";
        foreach ($values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $string = trim($buff, "&");
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . Yii::$app->params['wx_pay_key'];
        Yii::warning('wx_pay_string:' . $string);
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    public function makeMySign($values)
    {
        ksort($values);
        $buff = "";
        foreach ($values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $string = trim($buff, "&");
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->appPayKey;
        Yii::warning('wx_pay_string:' . $string);
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /*
 * 获取微信保存的媒体文件
 */
    public function getFile($media_id)
    {
        $token = $this->getAccessToken();
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=$token&media_id=$media_id";
        if ($data = Logic::request($url)) {
            $new_name = md5($media_id . rand(100, 999)) . '.amr';
            $file_name = Yii::getPathOfAlias('application.answer') . '/' . $new_name;
            file_put_contents($file_name, $data);
            return $new_name;
        }
        return false;
    }

    /*
 * 退款
 */
    public function refund($order_id, $money)
    {
        $refund_id = Logic::getOrderId();
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $param = [
            'appid' => $this->appId,
            'mch_id' => $this->appMchId,
            'nonce_str' => $this->getNonceStr(),
            'out_trade_no' => $order_id,
            'out_refund_no' => $refund_id,
            'total_fee' => $money * 100,
            'refund_fee' => $money * 100,
            'op_user_id' => $this->appMchId,
        ];
        $param['sign'] = $this->makeMySign($param);
//        var_dump($param);
        $xml = Logic::makeXML($param);
        $data = Logic::request($url, $xml, [], true, ['cert' => $this->certFile, 'key' => $this->certKey]);
//        var_dump($data);
        $result = json_decode(json_encode(simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if ($result['return_code'] != 'SUCCESS') {
            return false;
        }
        return $refund_id;
    }
}