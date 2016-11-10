<?php
namespace mycompany\common;

use Yii;
use yii\console;
use yii\web\Application;
use yii\base\Exception;
use yii\redis\Cache;
use yii\redis\Connection;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/7/29
 * Time: 下午9:39
 */
class Logic
{
    static public function makeResult($data = null, $pager = null, $r = 0, $msg = 'ok', $dict = false)
    {
        $result['result'] = $r;
        $result['msg'] = $msg;
        if ($data !== null) {
            $result['data'] = $data;
        }
        if (is_array($pager)) {
            list($result['all'], $result['page'], $result['all_page']) = $pager;
        }
        if ($dict) {
            echo json_encode($result, JSON_FORCE_OBJECT);
        } else {
            echo json_encode($result);
        }
    }

    static public function validatePhone($phone)
    {
        // TODO: better phone validation
        if (strlen($phone) != 11) {
            return false;
        }
        return true;
    }

    public static function request($url, $param = [], $header = [], $ssl = false, $files = [])
    {
        $ch = curl_init();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 10,
        ];
        if ($param) {
            $options[CURLOPT_POST] = 1;
            if (is_array($param)) {
                $options[CURLOPT_POSTFIELDS] = http_build_query($param);
            } else {
                $options[CURLOPT_POSTFIELDS] = $param;
            }
        }
        if ($header) {
            $options[CURLOPT_HTTPHEADER] = $header;
        }
        if ($ssl) {
            if ($files && isset($files['cert']) && isset($files['key'])) {
                curl_setopt($ch, CURLOPT_SSLCERT, $files['cert']);
                curl_setopt($ch, CURLOPT_SSLKEY, $files['key']);
            } else {
                $options[CURLOPT_SSL_VERIFYPEER] = false;
                $options[CURLOPT_SSL_VERIFYHOST] = false;
                $options[CURLOPT_SSLVERSION] = 1;
            }
        }
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    static public function setSMSCode($phone, $code, $uid = 0)
    {
        Yii::$app->redis->setex('phone_confirm:' . $uid . ':' . $phone, 3600, $code);
    }

    static public function getSMSCode($phone, $uid = 0, $del = 0)
    {
        $key = 'phone_confirm:' . $uid . ':' . $phone;
        $code = Yii::$app->redis->get($key);
        if ($del) {
            Yii::$app->redis->del($key);
        }
        return $code;
    }

    static public function strBanned($str, $replace = false)
    {
        $str = trim($str);
        $word_banned = Yii::$app->redis->hget('common_data', 'word_banned');
        $banned = explode("|", $word_banned);
        $word = [];
        foreach ($banned as $k => $v) {
            $word[$k] = preg_replace("/\\\{(\d+)\\\}/", ".{0,\\1}", preg_quote($v, '/'));
        }
        $preg = '/(' . implode('|', $word) . ')/iu';

        // 进行正则匹配
        if ($preg && preg_match($preg, $str) && $replace == false) {
            return true;
        } else if ($replace == true && $preg) {
            return preg_replace($preg, '***', $str);
        }
    }

    // TODO: connection retry
    static public function server_request($ip, $port, $cmd, $param = [], $multi = false)
    {
        $r = [];
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket !== false) {
            $result = socket_connect($socket, $ip, $port);
            if ($result !== false) {
                if ($multi) {
                    foreach ($param as $one) {
                        $pkg = self::makePkg($cmd, $one);
                        socket_write($socket, $pkg, strlen($pkg));
                    }
                } else {
                    $pkg = self::makePkg($cmd, $param);
                    socket_write($socket, $pkg, strlen($pkg));
                }
                socket_close($socket);
            }
        }
        return $r;
    }

    static public function makePkg($cmd, $data = '')
    {
        if ($data) {
            $json_str = json_encode($data);
        } else {
            $json_str = '';
        }
        $head = pack("nCC", strlen($json_str), 0, $cmd);
        return $head . $json_str;
    }

    static public function sendSMS($phone, $msg, $withTail = true)
    {
        $post_data = [];
        if (is_array($phone)) {
            $post_data['mobile'] = implode(',', $phone);
        } else {
            $post_data['mobile'] = $phone;
        }

        if ($withTail) {
            $msg .= '（客服电话400-9697-169 /微信服务号：大咖说）请勿回复本短信';
        }

        $post_data['account'] = iconv('GB2312', 'GB2312', "yingyu");
        $post_data['pswd'] = iconv('GB2312', 'GB2312', "Yingyu123");
        $post_data['msg'] = mb_convert_encoding($msg, 'UTF-8', 'auto');
        $url = 'http://222.73.117.156/msg/HttpBatchSendSM?';
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        return curl_exec($ch);
    }

    /*
     * $scene_type: 1 订单   2 聊天
     * $role: 1 发给学员   2 发给大咖
     * $device_type: 2 android  3 iOS
     */
    static public function push($device_id, $device_type, $content, $meet_id, $role, $scene_type = 1)
    {
        $argu = [
            'data' => [
                'alert' => $content,
                'action' => 'com.yyj.dakashuo.receive.push.msg',
                'meet_id' => $meet_id,
                'role' => $role,
                'type' => $scene_type,
                'title' => '“大咖说”预约提醒',
            ],
            'prod' => Yii::$app->params['lean_cloud_push_type'],
        ];
        if ($device_type == 2) {
            $argu['where'] = ['installationId' => $device_id];
        } elseif ($device_type == 3) {
            $argu['where'] = ['deviceToken' => $device_id];
        } else {
            return false;
        }
//        Yii::log('push:' . json_encode($argu), 'warning');
        return Logic::request('https://api.leancloud.cn/1.1/push',
            json_encode($argu),
            [
                'X-LC-Id: ' . Yii::$app->params['lean_cloud_id'],
                'X-LC-Key: ' . Yii::$app->params['lean_cloud_key'],
                'Content-Type: application/json',
            ]);
    }

    static public function weixinPush($open_id, $param, $target_url)
    {
        //$token = Yii::app()->redis8->getClient()->get('wx_token');
        $token = Yii::$app->redis8->get('wx_token');
        if (!$token) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' .
                Yii::$app->params['wx_id'] . '&secret=' . Yii::$app->params['wx_key'];
            $r = Logic::request($url);
            if ($r) {
                $result = json_decode($r, true);
                $token = $result['access_token'];
                Yii::$app->redis->setex('weixin_token', 3600, $token);
            } else {
                return '';
            }
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token;
        $param = [
            'touser' => $open_id,
            'template_id' => Yii::$app->params['weixin_push_template'],
            'url' => $target_url,
            'data' => [
                'first' => [
                    'value' => $param['first'],
                ],
                'OrderSn' => [
                    'value' => $param['id'],
                ],
                'OrderStatus' => [
                    'value' => $param['status'],
                ],
                'remark' => [
                    'value' => "\n" . $param['content'],
                ],
            ],
        ];
        return Logic::request($url, json_encode($param));
    }

    /**
     * 友好时间显示
     *
     * @param int $sTime 待显示的时间
     * @param string $type 类型. normal | mohu | full | ymd | other
     * @return string
     */
    static public function friendlyDate($sTime, $type = 'normal')
    {
        if (!$sTime)
            return '';
        //sTime=源时间，cTime=当前时间，dTime=时间差
        $cTime = time();
        $dTime = $cTime - $sTime;
        $dDay = intval(date("z", $cTime)) - intval(date("z", $sTime));
        //$dDay     =   intval($dTime/3600/24);
        $dYear = intval(date("Y", $cTime)) - intval(date("Y", $sTime));
        //normal：n秒前，n分钟前，n小时前，日期
        if ($type == 'normal') {
            if ($dTime < 60) {
                if ($dTime < 10) {
                    return '刚刚';    //by yangjs
                } else {
                    return intval(floor($dTime / 10) * 10) . "秒前";
                }
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
                //今天的数据.年份相同.日期相同.
            } elseif ($dYear == 0 && $dDay == 0) {
                //return intval($dTime/3600)."小时前";
                return '今天' . date('H:i', $sTime);
            } elseif ($dYear == 0) {
                return date("m月d日 H:i", $sTime);
            } else {
                return date("Y-m-d H:i", $sTime);
            }
        } elseif ($type == 'mohu') {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dDay > 0 && $dDay <= 7) {
                return intval($dDay) . "天前";
            } elseif ($dDay > 7 && $dDay <= 30) {
                return intval($dDay / 7) . '周前';
            } elseif ($dDay > 30) {
                return intval($dDay / 30) . '个月前';
            }
            //full: Y-m-d , H:i:s
        } elseif ($type == 'full') {
            return date("Y-m-d , H:i:s", $sTime);
        } elseif ($type == 'ymd') {
            return date("Y-m-d", $sTime);
        } else {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dYear == 0) {
                return date("Y-m-d H:i:s", $sTime);
            } else {
                return date("Y-m-d H:i:s", $sTime);
            }
        }
        return '';
    }

    static public function validatePassword($password)
    {
        // TODO: make it
        if (strlen($password) < 6 || strlen($password) > 32) {
            return false;
        }
        return true;
    }

    static public function validateName($name)
    {
        $len = mb_strlen($name, 'utf8');
        if ($len < 1 || $len > 10) {
            return false;
        }
        return true;
    }

    static public function uploadImage($type, $uid, $name = '')
    {
        if (!$name) {
            $name = $type;
        }

        if (!$_FILES || !isset($_FILES[$name])) {
            return [51, '文件不存在'];
        }
        $file = $_FILES[$name];
        if ((($file["type"] == "image/gif")  // TODO: right picture type check
                || ($file["type"] == "image/jpeg")
                || ($file["type"] == "image/jpg")
                || ($file["type"] == "image/pjpeg")
                || ($file["type"] == "image/x-png")
                || ($file["type"] == "image/png")
                || ($file["type"] == "multipart/form-data")
                || ($file["type"] == "application/octet-stream")
            )
            && ($file["size"] <= 1024 * 8000)
        ) {
            if ($file["error"] > 0) {
                return [52, "错误: " . $file["error"]];
            } else {
                $i = pathinfo($file["name"]);
                $new_name = "i_" . $uid . "_" . md5($file["name"] . rand(100, 999)) . '.' . $i['extension'];
                $new_file = Yii::getAlias('@images/' . $type . '/' . $new_name);
                move_uploaded_file($file["tmp_name"], $new_file);
                self::fixImage($new_file);
                return [0, $new_name];
            }
        } else {
            return [53, "请检查文件的尺寸和大小！"];
        }
    }

    static public function uploadFile($type, $name = '')
    {
        if (!$name) {
            $name = $type;
        }

        if (!$_FILES || !isset($_FILES[$name])) {
            return [51, '文件不存在'];
        }
        $file = $_FILES[$name];
        if ($file["size"] <= 1024 * 8000) {
            if ($file["error"] > 0) {
                return [52, "错误: " . $file["error"]];
            } else {
                $i = pathinfo($file["name"]);
                $new_name = md5($file["name"] . rand(100, 999)) . '.' . $i['extension'];
                $new_file = Yii::getAlias('@dksfile/' . $type . '/' . $new_name);
                move_uploaded_file($file["tmp_name"], $new_file);
                return [0, $new_name];
            }
        } else {
            return [53, "请检查文件的尺寸和大小！"];
        }
    }

    static public function fixImage($file)
    {
        $image = imagecreatefromjpeg($file);
        $exif = @exif_read_data($file);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;

                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;

                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
            imagejpeg($image, $file, 90);
        }
    }

    static public function get_order_sn($head = '')
    {
        $rand24 = mt_rand(10000000, 99999999) . mt_rand(10000000, 99999999) . mt_rand(10000000, 99999999);
        $rand8 = substr($rand24, mt_rand(0, 16), 8);
        return $head . date('ymd') . str_pad($rand8, 8, '0', STR_PAD_LEFT);
    }

    static public function formatDict($dict, $format)
    {
        if (isset($format['int'])) {
            foreach ($format['int'] as $key) {
                if (isset($dict[$key]) || is_null($dict[$key])) {
                    $dict[$key] = intval($dict[$key]);
                }
            }
        }
        if (isset($format['float'])) {
            foreach ($format['float'] as $key) {
                if (isset($dict[$key]) || is_null($dict[$key])) {
                    $dict[$key] = floatval($dict[$key]);
                }
            }
        }
        if (isset($format['str'])) {
            foreach ($format['str'] as $key) {
                if (isset($dict[$key]) || is_null($dict[$key])) {
                    $dict[$key] = strval($dict[$key]);
                }
            }
        }
        return $dict;
    }

    static public function imagePath($path, $type = '')
    {
        if (!$path) {
            if ($type == 'icon') {
                $path = '/images/sys/icon_default.png';
            }
            if ($type == 'cover') {
                $path = '/images/sys/cover_default.png';
            }
        }
        if ($path) {
            if (substr($path, 0, 7) == 'http://') {
                return $path;
            } else {
                return Yii::$app->params['img_host'] . $path;
            }
        } else {
            return '';
        }
    }

    static public function makeXML($param)
    {
        if (!is_array($param)
            || count($param) <= 0
        ) {
            return false;
        }

        $xml = "<xml>";
        foreach ($param as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    static public function sendMsg($chat_id, $msg)
    {
        $r = Logic::request('https://leancloud.cn/1.1/rtm/messages',
            json_encode([
                'conv_id' => $chat_id,
                'from_peer' => 'admin',
                'message' => $msg,
                'no_sync' => 'true',
            ]),
            [
                'X-LC-Id: ' . Yii::$app->params['lean_cloud_id'],
                'X-LC-Key: ' . Yii::$app->params['lean_cloud_master'] . ",master",
                'Content-Type: application/json',
            ]);
        return $r;
    }

    public static function getOrderId()
    {
        $rand24 = mt_rand(10000000, 99999999) . mt_rand(10000000, 99999999) . mt_rand(10000000, 99999999);
        $rand8 = substr($rand24, mt_rand(0, 16), 8);
        return date('ymd') . str_pad($rand8, 8, '0', STR_PAD_LEFT);
    }

    public static function getId()
    {
        return substr(date('ymd'), 1) . rand(10000, 99999);
    }

    public static function getMp3Seconds($file)
    {
        $seconds = false;
        $output = [];
        $full_file = Yii::getAlias('@answer' . '/' . $file);
        exec(Yii::$app->params['ffmpeg_cmd'] . " -i $full_file -f null - 2>&1", $output);
        foreach ($output as $line) {
            if (strstr($line, 'time') !== false) {
                $t1 = explode(' ', $line);
                $t2 = explode('=', $t1[1]);
                $t3 = explode(':', $t2[1]);
                if (intval($t3[1]) > 0) {
                    $seconds = 60;
                }else{
                    $seconds = intval(ceil(floatval($t3[2])));
                }
            }
        }
        return $seconds;
    }

    public static function amr2mp3($amr)
    {
        $path_parts = pathinfo($amr);
        $new_name = $path_parts['filename'] . '.mp3';
        $path = Yii::getAlias('@answer');
        $name = $path . '/' . $new_name;
        exec(Yii::$app->params['ffmpeg_cmd'] . " -i $path/$amr -ar 22050 -write_xing 0 $name");
        return $new_name;
    }
}