<?php
namespace mycompany\common;

use Yii;
use yii\base\Exception;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/8/30
 * Time: 下午3:42
 */
class ApiException
{
    const OK = 0;
    const TOKEN_FAIL = 1;
    const LOGIN_FAIL = 2;
    const EXPERT_NOT_EXIST = 3;
    const EXPERT_NO_TOPIC = 4;
    const MEET_NOT_EXIST = 5;
    const TOPIC_NOT_EXIST = 6;
    const NO_RIGHT = 7;
    const WRONG_PARAM = 8;
    const USER_NOT_EXPERT = 9;
    const USER_NOT_EXIST = 10;
    const USER_CLOSED = 11;

    static public function Msgs($code, $msg='', $e=false) {
        $msgs = self::getMsgs();
        $msg = isset($msgs[$code]) ? $msgs[$code] : $msg;
        var_dump($e);
        if($e){
            throw new Exception($msg, $code);
        }else{
            $result['result'] = $code;
            $result['msg'] = $msg;
            exit(json_encode($result));
        }

        //parent::__construct($msg, $code);
    }
    static public function getMsgs(){
        return $msgs = [
            self::OK => 'ok',
            self::TOKEN_FAIL => '请先登录',
            self::EXPERT_NOT_EXIST => '大咖不存在',
            self::EXPERT_NO_TOPIC => '大咖的话题不完整',
            self::LOGIN_FAIL => '用户名或密码错误',
            self::MEET_NOT_EXIST => '预约不存在',
            self::TOPIC_NOT_EXIST => '话题不存在',
            self::NO_RIGHT => '无操作权限',
            self::WRONG_PARAM => '参数错误',
            self::USER_NOT_EXPERT => '您不是大咖',
            self::USER_NOT_EXIST => '用户不存在',
            self::USER_CLOSED => '您的帐号已被冻结,您可以与我们的客服联系，了解冻结原因及申请解除冻结',
        ];
    }

//    static public function handle(ExceptionEvent $event){
//        $e = $event->exception;
//        if ($e instanceof ApiException && !Yii::$app->request->isAjax()) {
//            Logic::makeResult($e->data, $e->pager, $e->code, $e->message);
//            $event->handled = true;
//        }
//    }
}