<?php
namespace mycompany\common;

use Yii;
use yii\base\Object;
use yii\httpclient\Client;

/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/9/21
 * Time: 下午6:46
 */
class WeiXin extends Object
{
    public $appId = '';
    public $appSecret = '';
    public $appMchId = '';
    public $appPayKey = '';
    public $certFile = '';
    public $certKey = '';
    private $_isSub = null;

    public function getSignPackage($url)
    {
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

    public function getNonceStr($length = 32)
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
            $file_name = Yii::getAlias('@answer' . '/' . $new_name);
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

    /*
    * 接口方式获取用户信息
    */
    public function getInfoFromServer($open_id)
    {
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$open_id&lang=zh_CN";
        $info = Logic::request($url);
        return json_decode($info, true);
    }

    /*
    * 检查用户是否关注公众号
    */
    public function checkSub($open_id)
    {
        if ($this->_isSub === null) {
            $info = $this->getInfoFromServer($open_id);
            if (isset($info['subscribe']) && $info['subscribe'] == 1) {
                $this->_isSub = true;
            } else {
                $this->_isSub = false;
            }
        }
        return $this->_isSub;
    }

    public function codeToSession($code)
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setUrl("https://api.weixin.qq.com/sns/jscode2session")
            ->setData([
                'appid' => $this->appId,
                'secret' => $this->appSecret,
                'js_code' => $code,
                'grant_type' => 'authorization_code',
            ])
            ->send();
        if ($response->isOk && isset($response->data['openid'])) {
            return $response->data;
        }
        return false;
    }
}