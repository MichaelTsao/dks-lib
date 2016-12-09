<?php
namespace mycompany\common;

use Yii;
use Hashids\Hashids;

/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/7/29
 * Time: 下午9:39
 */
class Logic
{
    public static function makeID()
    {
        if (isset(Yii::$app->params['id_salt'])) {
            $salt = Yii::$app->params['id_salt'];
        } else {
            $salt = "I'm a salt!";
        }
        $h = new Hashids($salt, 0, 'abcdefghijklmnopqrstuvwxyz0123456789');
        return $h->encode(microtime(true) * 10000);
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
        return false;
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
                } else {
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