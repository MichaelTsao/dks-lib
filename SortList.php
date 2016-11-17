<?php
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 2016/11/17
 * Time: 下午2:42
 */

namespace mycompany\common;

class SortList extends RedisCommon
{
    public $key = null;

    public function create($data)
    {
        $this->redis->del($this->key);
        foreach ($data as $item => $value) {
            $this->redis->zadd($this->buildKey($this->key), $value, $item);
        }
    }
}