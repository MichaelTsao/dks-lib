<?php
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 2016/11/17
 * Time: 下午2:42
 */

namespace dakashuo\common;

class SortList extends RedisCommon
{
    public $sort = SORT_DESC;

    public function init()
    {
        parent::init();
        $this->makeKey();
    }

    public function makeKey()
    {
        return;
    }

    public function create($data)
    {
        $this->redis->del($this->key);
        $this->set($data);
    }

    public function set($data)
    {
        foreach ($data as $item => $value) {
            $this->redis->zadd($this->key, $value, $item);
        }
    }

    public function increase($item, $value = 1)
    {
        $this->redis->zincrby($this->key, $value, $item);
    }

    public function decrease($item, $value = -1)
    {
        $this->increase($item, $value);
    }

    public function count()
    {
        return $this->redis->zcount($this->key, '-inf', '+inf');
    }

    public function all()
    {
        return $this->get(0, -1);
    }

    public function page($page, $size)
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
        return $this->redis->$cmd($this->key, $begin, $end);
    }
}