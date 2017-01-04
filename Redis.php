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

/**
 * Redis common functions
 *
 * @property string $key
 * @property string $prefix
 * @property \yii\redis\Connection $redis
 *
 */
class Redis extends Object
{
    public $prefix = null;
    public $redis = null;
    protected $_key = null;

    public function init()
    {
        if (!$this->redis) {
            $this->redis = Yii::$app->redis;
            if (!$this->redis) {
                throw new Exception('Please Config the Redis');
            }
        }
    }

    public function setKey($key)
    {
        return $this->_key = $key;
    }

    public function getKey()
    {
        return $this->prefix . $this->_key;
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