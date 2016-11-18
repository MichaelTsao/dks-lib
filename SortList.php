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
    public $sort = SORT_DESC;

    public function create($data)
    {
        $this->redis->del($this->key);
        foreach ($data as $item => $value) {
            $this->redis->zadd($this->fullKey, $value, $item);
        }
        return true;
    }

    public function getAll()
    {
        return $this->get(0, -1);
    }

    public function getPage($page, $size)
    {
        $start = ($page - 1) * $size;
        $end = $page * $size - 1;
        return $this->get($start, $end);
    }

    public function get($begin, $end)
    {
        if ($this->sort == SORT_DESC) {
            $cmd = 'zrevrange';
        } else {
            $cmd = 'zrange';
        }
        var_dump($this->fullKey);
        return $this->redis->$cmd($this->fullKey, $begin, $end);
    }
}