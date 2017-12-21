<?php
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 16/1/3
 * Time: 上午9:26
 */

namespace dakashuo\common;

use yii\base\Object;
use yii\data\Pagination;

/**
 * Class Result
 * @package dakashuo\common
 * @property Pagination page
 * @property mixed data
 * @property int code
 * @property string msg
 */
class Result extends Object
{
    const OK = 0;
    const TOKEN_FAIL = 1;
    const LOGIN_FAIL = 2;
    const USER_CLOSED = 3;
    const WRONG_PARAM = 4;
    const FAIL = 99;

    private $msgs = [
        self::OK => 'ok',
        self::TOKEN_FAIL => '请先登录',
        self::LOGIN_FAIL => '用户名或密码错误',
        self::USER_CLOSED => '您的帐号已被冻结,您可以与我们的客服联系，了解冻结原因及申请解除冻结',
        self::WRONG_PARAM => '参数错误',
    ];

    private $_code = self::OK;
    private $_msg = '';
    public $data = null;
    public $page = null;

    public function getCode()
    {
        return $this->_code;
    }

    public function setCode($code)
    {
        $this->_code = intval($code);
    }

    public function setMsg($msg)
    {
        $this->_msg = $msg;
        if ($key = array_search($msg, $this->msgs) !== false) {
            $this->_code = $key;
        }elseif (in_array($this->_code, array_keys($this->msgs))) {
            $this->_code = self::FAIL;
        }
    }

    public function getMsg()
    {
        if ($this->_msg) {
            return $this->_msg;
        } elseif (isset($this->msgs[$this->_code])) {
            return $this->msgs[$this->_code];
        } else {
            return '系统错误';
        }
    }

    public function __toString()
    {
        $response['result'] = $this->code;
        $response['msg'] = $this->msg;
        if ($this->data) {
            $response['data'] = $this->data;
        }
        if ($this->page instanceof Pagination) {
            $response['all'] = $this->page->totalCount;
            $response['page'] = $this->page->page + 1;
            $response['all_page'] = $this->page->pageCount;
        }

        return json_encode($response);
    }
}