<?php
/**
 * Created by PhpStorm.
 * User: jinlin wang
 * Date: 2016/10/17
 * Time: 17:31
 */

namespace mycompany\common;

use Yii;
use yii\redis\Cache;
use yii\redis\Connection;

class RedisCommon extends Connection
{
    /**
     * 封装hmset方法
     * @param $key
     * @param array $info
     * @return bool
     */
    public static function setHash_Array($key,$info=[]){
        if($info===[] || !is_array($info))  ApiException::Msgs(99, '使用 setHash_Array 方法 参数 info 不是数组');
        foreach ($info as $k=>$v){
            Yii::$app->redis->hmset($key, $k, $v);
        }
        return true;
    }
}