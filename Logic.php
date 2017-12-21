<?php

namespace dakashuo\common;

use Hashids\Hashids;
use Yii;

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
        return $h->encode(intval(microtime(true) * 10000));
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
     * @param string $time 待显示的时间
     * @return string
     */
    static public function friendlyDate($time)
    {
        if (!$time) {
            return '';
        }

        $now = date_create();
        $date = date_create($time);
        $interval = $date->diff($now);

        $i = [
            'y' => '年',
            'm' => '月',
            'd' => '天',
            'h' => '小时',
            'i' => '分钟',
            's' => '秒',
        ];

        foreach ($i as $type => $name) {
            if ($interval->$type > 0) {
                return $interval->$type . $name . '前';
            }
        }
        return '刚刚';
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

    public static function getImageHost()
    {
        if (isset(Yii::$app->params['imageHost'])) {
            return Yii::$app->params['imageHost'];
        } else {
            return '';
        }
    }
}