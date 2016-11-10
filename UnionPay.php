<?php
namespace mycompany\common;

use Yii;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 16/8/15
 * Time: 下午3:02
 */
class UnionPay
{
    protected $certId = null;
    protected $certKey = null;
    protected $merId = '808080211304400';
    protected $private_key = [];

    public function getConsumeParam($order_sn, $price)
    {
        $params = [
            'version' => '5.0.0',                 //版本号
            'encoding' => 'utf-8',                  //编码方式
            'txnType' => '01',                      //交易类型
            'txnSubType' => '01',                  //交易子类
            'bizType' => '000201',                  //业务类型
            'frontUrl' => Yii::$app->params['web_host'] . "/unionpay/receive",      //后台通知地址
            'backUrl' => Yii::$app->params['api_host'] . "/pay/notify/from/union",      //后台通知地址
            'signMethod' => '01',                  //签名方法
            'channelType' => '08',                  //渠道类型，07-PC，08-手机
            'accessType' => '0',                  //接入类型
            'currencyCode' => '156',              //交易币种，境内商户固定156
            'merId' => Yii::$app->params['unionpay_mer_id'],        //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数

            'orderId' => $order_sn,    //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => date('YmdHis'),    //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => strval($price * 100),    //交易金额，单位分，此处默认取demo演示页面传递的参数

            // 'reqReserved' =>'透传信息',        //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据
            // 其他特殊用法请查看 special_use_purchase.php
        ];
        return $this->sign($params);
    }

    public function verify()
    {
        // init
        $params = $_POST;
        clearstatcache();
        $filePath = Yii::getAlias('@certs' . '/acp_prod_verify_sign.cer');

        // verify cert id
        list($cert_id, $cert_key) = $this->readCer($filePath);
        if ($cert_id != $params['certId']) {
            return false;
        }

        // verify signature
        $signature_str = $params['signature'];
        unset($params['signature']);
        $r = openssl_verify(
            sha1($this->makeParamString($params), FALSE),
            base64_decode($signature_str),
            file_get_contents($filePath),
            OPENSSL_ALGO_SHA1
        );
        if ($r != 1 || $params['respCode'] != '00') {
            return false;
        }

        return true;
    }

    public function refund($queryId, $money)
    {
        $orderId = Logic::getOrderId();
        $params = [
            'version' => '5.0.0',              //版本号
            'encoding' => 'utf-8',              //编码方式
            'signMethod' => '01',              //签名方法
            'txnType' => '04',                  //交易类型
            'txnSubType' => '00',              //交易子类
            'bizType' => '000201',              //业务类型
            'accessType' => '0',              //接入类型
            'channelType' => '07',              //渠道类型
            'backUrl' => Yii::$app->params['api_host'] . "/pay/unionNotify", //后台通知地址
            'merId' => Yii::$app->params['unionpay_mer_id'],            //商户代码，请改成自己的测试商户号，此处默认取demo演示页面传递的参数

            'orderId' => $orderId,        //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'origQryId' => $queryId, //原消费的queryId，可以从查询接口或者通知接口中获取，此处默认取demo演示页面传递的参数
            'txnTime' => date('YmdHis'),        //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'txnAmt' => strval($money * 100),       //交易金额，退货总金额需要小于等于原消费

            // 'reqReserved' =>'透传信息',            //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据
        ];
        $params = $this->sign($params);
        $r = Logic::request(Yii::$app->params['unionpay_server_gateway'], $params);
        parse_str($r, $return);
        if (isset($return['respCode']) && $return['respCode'] == '00') {
            return $orderId;
        } else {
            return false;
        }
    }

    public function payout($acct, $name, $bank, $money, $purpose, $orderId)
    {
        $params = [
            'merId' => $this->merId,
            'merDate' => date('Ymd'),
            'merSeqId' => $orderId,
            'cardNo' => $acct,
            'usrName' => mb_convert_encoding($name, "GBK"),
            'openBank' => mb_convert_encoding($bank, "GBK"),
            'prov' => mb_convert_encoding('大咖说', "GBK"),
            'city' => mb_convert_encoding('大咖说', "GBK"),
            'transAmt' => $money,
            'purpose' => mb_convert_encoding($purpose, "GBK"),
            'subBank' => mb_convert_encoding('某支行', "GBK"),
            'flag' => '00',
            'version' => '20151207',
            'termType' => '07',
            'payMode' => '1',
            'signFlag' => '1',
        ];
        if (!$params['chkValue'] = $this->encrypt($params)) {
            return false;
        }
//      var_dump($params);
        $r = Logic::request('http://sfj.chinapay.com/dac/SinPayServletGBK', $params);
//        var_dump($r);
//      echo "\n";
        parse_str($r, $output);
//      $plain = substr($r, 0, strripos($r, "&"));
//      var_dump($plain);
//      var_dump($output['chkValue']);
//      echo "\n";
//      $result = $this->verifyChinaPay($output['chkValue'], base64_encode($plain));
//      var_dump($result);
        return ($output['responseCode'] == '0000');
    }

    public function checkPayout($orderId)
    {
        $params = [
            'merId' => $this->merId,
            'merDate' => date('Ymd'),
            'merSeqId' => $orderId,
            'version' => '20090501',
            'signFlag' => '1',
        ];
        if (!$params['chkValue'] = $this->encrypt($params, 'check')) {
            return false;
        }
        $r = Logic::request('http://sfj.chinapay.com/dac/SinPayQueryServletGBK', $params);
        $a = explode('|', $r);
//        var_dump($a);
        if (!isset($a[14])) {
            return 3;
        }
        $t = $a[14];
        if ($t == 's') {
            return 1;
        }
        if ($t == '6' || $t == '9') {
            return 2;
        }
        return 3;
    }

    public function verifyChinaPay($key, $params)
    {
//        if (!array_key_exists("PGID", $this->private_key)) {
//            var_dump($this->private_key);
//            return false;
//        }
        if (strlen($key) != 256) {
            return false;
        }
        $hb = $this->sha1_128($params);
        $hbhex = strtoupper(bin2hex($hb));
        $rbhex = $this->rsa_decrypt($key);
        var_dump($hbhex);
        var_dump($rbhex);
        return $hbhex == $rbhex ? true : false;
    }

    private function encrypt($params, $type='payout')
    {
        $key_file = parse_ini_file(Yii::getAlias('@unionpay' . '/MerPrK_808080211304400_20160809154600.key'));
        if (!$key_file) {
            return false;
        }
        $this->private_key["MERID"] = $key_file['MERID'];
        $hex = substr($key_file["prikeyS"], 80);

        $bin = hex2bin($hex);
        $this->private_key["modulus"] = substr($bin, 0, 128);
        $cipher = MCRYPT_DES;
        $iv = str_repeat("\x00", 8);
        $prime1 = substr($bin, 384, 64);
        $des_key = 'SCUBEPGW';
        //mcrypt_cbc Warning: This function was DEPRECATED in PHP 5.5.0, and REMOVED in PHP 7.0.0.
        $enc = mcrypt_encrypt($cipher, $des_key, $prime1, MCRYPT_DECRYPT, $iv);
        $this->private_key["prime1"] = $enc;
        $prime2 = substr($bin, 448, 64);
        $enc = mcrypt_encrypt($cipher, $des_key, $prime2, MCRYPT_DECRYPT, $iv);
        $this->private_key["prime2"] = $enc;
        $prime_exponent1 = substr($bin, 512, 64);
        $enc = mcrypt_encrypt($cipher, $des_key, $prime_exponent1, MCRYPT_DECRYPT, $iv);
        $this->private_key["prime_exponent1"] = $enc;
        $prime_exponent2 = substr($bin, 576, 64);
        $enc = mcrypt_encrypt($cipher, $des_key, $prime_exponent2, MCRYPT_DECRYPT, $iv);
        $this->private_key["prime_exponent2"] = $enc;
        $coefficient = substr($bin, 640, 64);
        $enc = mcrypt_encrypt($cipher, $des_key, $coefficient, MCRYPT_DECRYPT, $iv);
        $this->private_key["coefficient"] = $enc;

        $hb = $this->sha1_128($this->makeParamsString($params, $type));
        return $this->rsa_encrypt($hb);
    }

    private function makeParamsString($params, $type)
    {
        $str = '';
        if ($type == 'payout') {
            $str = $params['merId'] . $params['merDate'] . $params['merSeqId'] . $params['cardNo'] . $params['usrName']
                . $params['openBank'] . $params['prov'] . $params['city'] . $params['transAmt'] . $params['purpose']
                . $params['subBank'] . $params['flag'] . $params['version'] . $params['termType'] . $params['payMode'];
        } elseif ($type == 'check') {
            $str = $params['merId'] . $params['merDate'] . $params['merSeqId'] . $params['version'];
        }
        return base64_encode($str);
    }

    private function sha1_128($string)
    {
        $hash = sha1($string);
        $sha_bin = hex2bin($hash);
        $sha_pad = hex2bin("0001ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff003021300906052b0e03021a05000414");
        return $sha_pad . $sha_bin;
    }

    private function rsa_encrypt($input)
    {
        $p = $this->bin2int($this->private_key["prime1"]);
        $q = $this->bin2int($this->private_key["prime2"]);
        $u = $this->bin2int($this->private_key["coefficient"]);
        $dP = $this->bin2int($this->private_key["prime_exponent1"]);
        $dQ = $this->bin2int($this->private_key["prime_exponent2"]);
        $c = $this->bin2int($input);
        $cp = bcmod($c, $p);
        $cq = bcmod($c, $q);
        $a = bcpowmod($cp, $dP, $p);
        $b = bcpowmod($cq, $dQ, $q);
        if (bccomp($a, $b) >= 0) {
            $result = bcsub($a, $b);
        } else {
            $result = bcsub($b, $a);
            $result = bcsub($p, $result);
        }
        $result = bcmod($result, $p);
        $result = bcmul($result, $u);
        $result = bcmod($result, $p);
        $result = bcmul($result, $q);
        $result = bcadd($result, $b);
        $ret = $this->bcdechex($result);
        $ret = strtoupper($this->padstr($ret));
        return (strlen($ret) == 256) ? $ret : false;
    }

    private function rsa_decrypt($input)
    {
        $check = $this->bchexdec($input);
        $modulus = $this->bin2int($this->private_key["modulus"]);
        $exponent = $this->bchexdec("010001");
        $result = bcpowmod($check, $exponent, $modulus);
        $rb = $this->bcdechex($result);
        return strtoupper($this->padstr($rb));
    }

    private function bcdechex($decdata)
    {
        $s = $decdata;
        $ret = '';
        while ($s != '0') {
            $m = bcmod($s, '16');
            $s = bcdiv($s, '16');
            $hex = dechex($m);
            $ret = $hex . $ret;
        }
        return $ret;
    }

    private function padstr($src, $len = 256, $chr = '0', $d = 'L')
    {
        $ret = trim($src);
        $padlen = $len - strlen($ret);
        if ($padlen > 0) {
            $pad = str_repeat($chr, $padlen);
            if (strtoupper($d) == 'L') {
                $ret = $pad . $ret;
            } else {
                $ret = $ret . $pad;
            }
        }
        return $ret;
    }

    private function bin2int($bindata)
    {
        $hexdata = bin2hex($bindata);
        return $this->bchexdec($hexdata);
    }

    private function bchexdec($hexdata)
    {
        $ret = '0';
        $len = strlen($hexdata);
        for ($i = 0; $i < $len; $i++) {
            $hex = substr($hexdata, $i, 1);
            $dec = hexdec($hex);
            $exp = $len - $i - 1;
            $pow = bcpow('16', $exp);
            $tmp = bcmul($dec, $pow);
            $ret = bcadd($ret, $tmp);
        }
        return $ret;
    }

    public function payout_old($acct, $name, $person_id, $money)
    {
        list($certId, $certKey) = $this->readCer(
            Yii::$app->params['unionpay_cert_cer']
        );
        $params = [
            'version' => '5.0.0',              //版本号
            'encoding' => 'utf-8',              //编码方式
            'signMethod' => '01',              //签名方法
            'txnType' => '12',                  //交易类型
            'txnSubType' => '00',              //交易子类
            'bizType' => '000401',              //业务类型
            'accessType' => '0',              //接入类型
            'channelType' => '08',              //渠道类型
            'currencyCode' => '156',          //交易币种，境内商户勿改
            'backUrl' => Yii::$app->params['api_host'] . "/pay/unionNotify", //后台通知地址
            'encryptCertId' => $certId,     //验签证书序列号
            'merId' => Yii::$app->params['unionpay_mer_id'],            //商户代码，请改成自己的测试商户号，此处默认取demo演示页面传递的参数

            'orderId' => Logic::get_order_sn(),        //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'txnTime' => date('YmdHis'),        //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'txnAmt' => strval($money * 100),       //交易金额，退货总金额需要小于等于原消费
            'accNo' => $acct,  //$this->encrypt($acct, $certKey),     //卡号，新规范请按此方式填写
            'customerInfo' => base64_encode("{" . $this->makeParamString([
                    'certifTp' => '01',
                    'certifId' => $person_id,
                    'customerNm' => $name,
                ], 0) . "}"), //持卡人身份信息，新规范请按此方式填写

            // 'reqReserved' =>'透传信息',            //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据
        ];
        $params = $this->sign($params);
        $r = Logic::request(Yii::$app->params['unionpay_server_gateway'], $params);
        parse_str($r, $return);
        if (isset($return['respCode']) && $return['respCode'] == '00') {
            return true;
        } else {
            return false;
        }
    }

    private function makeParamString($params, $sort = 1)
    {
        if ($sort) {
            ksort($params);
            reset($params);
        }
        $params_str = '';
        while (list ($key, $value) = each($params)) {
            $params_str .= $key . "=" . $value . "&";
        }
        return substr($params_str, 0, count($params_str) - 2);
    }

    private function readPfx($file, $password)
    {
        if (!$this->certId || !$this->certKey) {
            openssl_pkcs12_read(file_get_contents($file), $certs, $password);
            $x509data = $certs['cert'];
            openssl_x509_read($x509data);
            $certData = openssl_x509_parse($x509data);
            $this->certId = $certData['serialNumber'];
            $this->certKey = $certs['pkey'];
        }
        return [$this->certId, $this->certKey];
    }

    private function readCer($file)
    {
        $x509data = file_get_contents($file);
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        return [$certdata ['serialNumber'], $x509data];
    }

    private function makeSignature($params, $key)
    {
        openssl_sign(sha1($this->makeParamString($params), FALSE), $signature, $key, OPENSSL_ALGO_SHA1);
        return base64_encode($signature);
    }

    private function sign($params)
    {
        list($certId, $certKey) = $this->readPfx(
            Yii::$app->params['unionpay_cert'],
            Yii::$app->params['unionpay_cert_password']
        );
        $params['certId'] = $certId;
        $params ['signature'] = $this->makeSignature($params, $certKey);
        return $params;
    }
}