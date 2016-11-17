<?php
/**
 * Created by PhpStorm.
 * User: jinlin wang
 * Date: 2016/10/17
 * Time: 17:31
 */

namespace mycompany\common;

use Yii;
use yii\base\Exception;
use yii\base\Object;

class RedisCommon extends Object
{
    public $prefix = null;
    public $redis = null;

    public function init()
    {
        if (!$this->redis) {
            var_dump(Yii::$app->redis);
            if (isset(Yii::$app->redis)) {
                $this->redis = Yii::$app->redis;
            }else{
                throw new Exception('Please Config the Redis');
            }
        }
    }

    public function buildKey($key)
    {
        return $this->prefix . $key;
    }

    /**
     * 封装hmset方法
     * @param $key
     * @param array $info
     * @return bool
     */
    static public function setHash_Array($key, $info = [])
    {
        if ($info === [] || !is_array($info)) ApiException::Msgs(99, '使用 setHash_Array 方法 参数 info 不是数组');
        foreach ($info as $k => $v) {
            Yii::$app->redis->hmset($key, $k, $v);
        }
        return true;
    }

    static public function hashToArray($r)
    {
        $d = [];
        $n = 1;
        $l = '';
        foreach ($r as $k => $v) {
            if ($n) {
                $d[$v] = '';
                $l = $v;
                $n = 0;
            } else {
                $d[$l] = $v;
                $n = 1;
            }
        }
        return $d;
    }
}