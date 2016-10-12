<?php
namespace mycompany\common;

use Yii;
use yii\web\Application;
use yii\console;
use yii\db;
use yii\redis\Cache;
use yii\redis\Connection;
use mycompany\business;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/8/16
 * Time: 上午11:23
 */
class Meet
{
    const CREATE = 11;
    const AUDIT_REFUSE = 12;
    const AUDIT_ACCEPT = 1;
    const EXPERT_ACCEPT = 2;
    const EXPERT_REFUSE = 3;
    const EXPERT_TIMEOUT = 4;
    const USER_PAY = 10;  // old: 5
    const USER_PAY_TIMEOUT = 6;
    const MEET = 7;
    const COMMENT = 8;
    const USER_CANCEL = 9;
    //const CHAT = 10;  // 已弃用
    const ADMIN_CANCEL = 13;

    const TYPE_ALL = 0;
    const TYPE_SINGLE = 1;
    const TYPE_PERIOD = 2;
    const TYPE_LESSON = 3;
    const TYPE_ASK = 4;

    static public function info($meet_id)
    {
        $key = "meet:" . $meet_id;
        $info = Yii::$app->redis->hgetall($key);
        if (!$info || count($info) != 38) {
            $data = business\MeetDB::model()->findOne($meet_id)->toArray();
            if ($data) {
                $info = $data;
                if ($info['meet_type'] == Meet::TYPE_SINGLE) {
                    $h = $info['minutes'] / 60;
                    if ($h == 0.5) {
                        $hours = '半小时';
                    } else {
                        $hours = $h . "小时";
                    }
                    $info['hours'] = $hours;
                    if ($info['user_price'] > -1) {
                        $p = ceil($info['user_price'] / (1 + $info['fee_rate']));
                        $info['origin_price'] = $p . "元";
                        $info['fee'] = strval($info['user_price'] - $p) . "元";
                        $info['price'] = strval($info['user_price']) . "元";
                    } else {
                        $price = $h * $info['price'];
                        if ($info['price_type'] == 1) {
                            $info['origin_price'] = strval($price) . "元";
                            $info['fee'] = strval($price * $info['fee_rate']) . "元";
                            $info['price'] = strval($price * (1 + $info['fee_rate'])) . "元";
                        } else {
                            $info['origin_price'] = strval($price * (1 - $info['fee_rate'])) . "元"; // 大咖的钱
                            $info['price'] = strval($price) . "元"; // 收用户的钱
                            $info['fee'] = strval($price * $info['fee_rate']) . "元"; // 公司收的服务费
                        }
                    }
                } elseif ($info['meet_type'] == Meet::TYPE_PERIOD) {
                    $info['hours'] = $info['minutes'] . '天';
                    $price = intval($info['price']) * intval($info['period_length']);
                    if ($info['price_type'] == 1) {
                        $info['origin_price'] = strval($price) . "元";
                        $info['fee'] = strval($price * $info['fee_rate']) . "元";
                        $info['price'] = strval($price * (1 + $info['fee_rate'])) . "元";
                    } else {
                        $info['origin_price'] = strval($price * (1 - $info['fee_rate'])) . "元"; // 大咖的钱
                        $info['fee'] = strval($price * $info['fee_rate']) . "元"; // 公司收的服务费
                        $info['price'] = strval($price) . "元"; // 收用户的钱
                    }
                } elseif ($info['meet_type'] == Meet::TYPE_LESSON) {
                    $info['hours'] = $info['minutes'] . '小时';
                    $price = $info['price'];
                    if ($info['price_type'] == 1) {
                        $info['origin_price'] = strval($price) . "元";
                        $info['fee'] = strval($price * $info['fee_rate']) . "元";
                        $info['price'] = strval($price * (1 + $info['fee_rate'])) . "元";
                    } else {
                        $info['origin_price'] = strval($price * (1 - $info['fee_rate'])) . "元"; // 大咖的钱
                        $info['price'] = strval($price) . "元"; // 收用户的钱
                        $info['fee'] = strval($price * $info['fee_rate']) . "元"; // 公司收的服务费
                    }

                } elseif ($info['meet_type'] == Meet::TYPE_ASK) {
                    $info['hours'] = 0;
                    $price = $info['price'];
                    $info['origin_price'] = strval($price * (1 - $info['fee_rate'])) . "元"; // 大咖的钱
                    $info['price'] = strval($price) . "元"; // 收用户的钱
                    $info['fee'] = strval($price * $info['fee_rate']) . "元"; // 公司收的服务费
                }
                $info['fee_rate'] = floatval($info['fee_rate']);
                Yii::$app->redis->hmset($key, $info);
            } else {
                throw new ApiException(ApiException::MEET_NOT_EXIST);
            }
        }

        $sql = "select refund_reason, confirm_time from meet_refund where meet_id=$meet_id and tranfer_result=1";
        if ($refund = Yii::app()->db->createCommand($sql)->queryRow()) {
            $info['refund_time'] = $refund['confirm_time'];
            $info['refund_reason'] = $refund['refund_reason'];
        }else{
            $info['refund_time'] = '';
            $info['refund_reason'] = '';
        }

        $comment_images = array();
        $sql = "select image from meet_comment_img where meet_id=$meet_id and status=1 ORDER BY sort";
        $data = Yii::app()->db->createCommand($sql)->queryAll();
        foreach ($data as $item) {
            $comment_images[] = Logic::imagePath($item['image'], 'comment');
        }
        $info['comment_image'] = $comment_images;

        if ($info['meet_type'] == Meet::TYPE_ASK) {
            $user_status = Yii::app()->params['ask_user_status'];
            $expert_status = Yii::app()->params['ask_expert_status'];
            $user_cancel_status = Yii::app()->params['ask_cancel_user_status'];
            $expert_cancel_status = Yii::app()->params['ask_cancel_expert_status'];
        } else {
            $user_status = Yii::app()->params['meet_user_status'];
            $expert_status = Yii::app()->params['meet_expert_status'];
            $user_cancel_status = Yii::app()->params['cancel_user_status'];
            $expert_cancel_status = Yii::app()->params['cancel_expert_status'];
        }
        if ($info['status'] == Meet::ADMIN_CANCEL && $info['refund_time']) {
            $info['user_status'] = $info['expert_status'] = '已退款';
        }else{
            $info['user_status'] = $user_status[$info['status']];
            $info['expert_status'] = $expert_status[$info['status']];
        }
        if (isset($user_cancel_status[$info['status']])) {
            if ($info['status'] == Meet::USER_CANCEL) {
                $info['user_cancel_status'] = $info['comment'];
                $info['expert_cancel_status'] = $info['comment'];
            } elseif (in_array($info['status'], array(Meet::EXPERT_REFUSE, Meet::AUDIT_REFUSE))) {
                $info['user_cancel_status'] = $info['refuse_reason'];
                $info['expert_cancel_status'] = $info['refuse_reason'];
            } else {
                $info['user_cancel_status'] = $user_cancel_status[$info['status']];
                $info['expert_cancel_status'] = $expert_cancel_status[$info['status']];
            }
        } else {
            $info['user_cancel_status'] = $info['expert_cancel_status'] = '';
        }

        $info['user_remind'] = isset(Yii::app()->params['meet_user_remind'][$info['status']]) ?
            Yii::app()->params['meet_user_remind'][$info['status']] : '';
        $info['expert_remind'] = isset(Yii::app()->params['meet_expert_remind'][$info['status']]) ?
            Yii::app()->params['meet_expert_remind'][$info['status']] : '';

        $sql = "select id from meet_withdraw where meet_id=$meet_id and status=3";
        $w = Yii::app()->db->createCommand($sql)->queryScalar();
        if ($w) {
            $info['withdraw'] = 1;
        } else {
            $info['withdraw'] = 0;
        }

        if ($info['meet_type'] == Meet::TYPE_LESSON) {
            $sql = "SELECT lesson_name, lesson_price, lesson_hour FROM expert_lesson WHERE id=" . $info['topic_id'];
            $info['lesson'] = Yii::app()->db->createCommand($sql)->queryRow();
        } else {
            $info['lesson'] = new stdClass();
        }

        return $info;
    }

    static public function create($meet_type, $uid, $expert_id, $topic_id, $question, $intro, $user_price = -1, $period_length = 1, $platform = 0)
    {
        $expert = Expert::info($expert_id);
        if ($meet_type == self::TYPE_SINGLE && !$expert['access_status']) {
            throw new ApiException(61, '该行家暂停预约');
        }
        if ($meet_type == self::TYPE_PERIOD && !$expert['period_status']) {
            throw new ApiException(61, '该行家暂停预约');
        }
        if ($meet_type == self::TYPE_LESSON && !$expert['lesson_status']) {
            throw new ApiException(61, '该行家暂停预约');
        }
        if ($meet_type == self::TYPE_ASK && !$expert['ask_status']) {
            throw new ApiException(61, '该行家暂停预约');
        }

        if ($uid == $expert['uid']) {
            throw new ApiException(60, '您不能自己约见自己');
        }

        $meet = new MeetDB();
        $meet->meet_type = $meet_type;
        $meet->period_length = $period_length;
        $meet->topic_id = $topic_id;
        $meet->uid = $uid;
        $meet->expert_id = $expert_id;
        if ($meet_type == Meet::TYPE_SINGLE) {
            $meet->minutes = $expert['meet_hour'] * 60;
            $meet->price = $expert['real_price'];
        } elseif ($meet_type == Meet::TYPE_PERIOD) {
            $meet->minutes = $expert['period_length'];
            $meet->price = $expert['period_price'];
        } elseif ($meet_type == Meet::TYPE_LESSON) {
            if ($lesson = Yii::app()->db->createCommand("SELECT * FROM expert_lesson WHERE id=:id AND lesson_status=1")
                ->bindParam(':id', $topic_id)->queryRow()
            ) {
                $meet->minutes = intval($lesson['lesson_hour']);
                $meet->price = $lesson['lesson_price'];
            } else {
                throw new ApiException(ApiException::WRONG_PARAM);
            }
        } elseif ($meet_type == Meet::TYPE_ASK) {
            $meet->price = $user_price;
            $user_price = -1;
            $meet->minutes = 0;
        } else {
            throw new ApiException(ApiException::WRONG_PARAM);
        }
        $meet->fee_rate = Yii::app()->params['fee_rate'];
        $meet->user_price = $user_price;
        $meet->question = $question;
        $meet->intro = $intro;
        $meet->status = self::CREATE;
        $meet->platform = $platform;
        $meet->price_type = Yii::app()->params['price_type'];
        $chat_id = Meet::createConversation(array(strval($uid), strval($expert['uid'])));
        if ($chat_id) {
            $meet->chat_id = $chat_id;
        }
        $meet->chat_status = 0;
        $meet->show_id = Logic::getOrderId();
        if ($meet_type == self::TYPE_ASK) {
            $meet->pay_time = date('Y-m-d H:i:s');
        }
        $meet->save();
        Meet::info($meet->meet_id);

        Yii::$app->redis->zadd('user_meet:' . $uid, time(), $meet->meet_id);
        self::setRun($meet->meet_id, 'user', $uid);
        return $meet->meet_id;
    }

    static public function comment($expert_id, $page, $size, $type = 1)
    {
        $start = ($page - 1) * $size;
        $end = $page * $size - 1;
        if ($type == 1) {
            $key = 'expert_comment:' . $expert_id;
        } else {
            $key = 'expert_comment_image:' . $expert_id;
        }
        $comment = Yii::$app->redis->zrevrange($key, $start, $end);
        $all = Yii::$app->redis->zcount($key, '-inf', '+inf');

        $result = array();
        foreach ($comment as $key => $c) {
            $comment_one = [];
            if (intval($c) > 0) {
                $info = Meet::info($c);
                $comment_one['uid'] = $info['uid'];
                $comment_one['rate'] = $info['rate'];
                $comment_one['topic_id'] = $info['topic_id'];
                $comment_one['comment_time'] = $info['comment_time'];
                $comment_one['comment'] = $info['comment'];
                if ($info['topic_id'] && $info['meet_type'] != Meet::TYPE_LESSON) {
                    $topic = Expert::topicInfo($info['topic_id']);
                    $topic_name = $topic['name'];
                } else {
                    $topic_name = '';
                }
                $comment_one['topic_name'] = $topic_name;
                $comment_one['topic'] = $topic_name;
                $user = User::info($info['uid']);
                $comment_one['username'] = $user['realname'];
                $comment_one['icon'] = $user['icon'];
                $comment_one['company'] = $user['company'];
                $comment_one['title'] = $user['title'];

                $comment_one['image'] = array();
                $sql = "select image from meet_comment_img where meet_id=$c and status=1 ORDER BY sort";
                $data = Yii::$app->db->createCommand($sql)->queryAll();
                foreach ($data as $item) {
                    $comment_one['image'][] = Logic::imagePath($item['image'], 'comment');
                }
            } else {
                $info = json_decode($c, true);
                $comment_one['uid'] = rand(100000, 999999);
                $comment_one['rate'] = $info['rate'];
                $comment_one['topic_id'] = $info['topic_id'];
                $comment_one['comment_time'] = $info['comment_time'];
                $comment_one['comment'] = $info['comment'];
                if ($info['topic_id']) {
//                    $topic = Expert::topicInfo($info['topic_id']);
//                    $topic_name = $topic['name'];
                    $topic_name = '';
                } else {
                    $topic_name = '';
                }
                $comment_one['topic_name'] = $topic_name;
                $comment_one['topic'] = $topic_name;
                $comment_one['username'] = $info['username'];
                $comment_one['icon'] = Logic::imagePath($info['icon'], 'icon');
                $comment_one['company'] = '';
                $comment_one['title'] = '';
                $comment_one['image'] = array();
            }

            $result[$key] = Logic::formatDict($comment_one, array(
                'int' => ['uid', 'rate', 'topic_id'],
                'str' => ['comment', 'comment_time', 'topic_name', 'username', 'icon', 'topic'],
            ));
        }
        return [$result, $all];
    }

    static public function commentWithImg($expert_id)
    {
        $key = 'expert_comment:' . $expert_id;
        $comment = Yii::$app->redis->zrevrange($key, 0, -1);
        $all = Yii::$app->redis->zcount($key, '-inf', '+inf');

        $result = array();
        foreach ($comment as $key => $c) {
            $comment_one = [];
            if (intval($c) > 0) {
                $sql = "select image from meet_comment_img where meet_id=$c and status=1 ORDER BY sort";
                $data = Yii::$app->db->createCommand($sql)->queryAll();
                foreach ($data as $item) {
                    $comment_one['image'][] = Logic::imagePath($item['image'], 'comment');
                }

                if (isset($comment_one['image'])) {
                    $info = Meet::info($c);
                    $comment_one['uid'] = $info['uid'];
                    $comment_one['rate'] = $info['rate'];
                    $comment_one['topic_id'] = $info['topic_id'];
                    $comment_one['comment_time'] = $info['comment_time'];
                    $comment_one['comment'] = $info['comment'];
                    if ($info['topic_id']) {
                        $topic = Expert::topicInfo($info['topic_id']);
                        $topic_name = $topic['name'];
                    } else {
                        $topic_name = '';
                    }
                    $comment_one['topic_name'] = $topic_name;
                    $comment_one['topic'] = $topic_name;
                    $user = User::info($info['uid']);
                    $comment_one['username'] = $user['realname'];
                    $comment_one['icon'] = $user['icon'];
                    $comment_one['company'] = $user['company'];
                    $comment_one['title'] = $user['title'];

                    $result[] = Logic::formatDict($comment_one, array(
                        'int' => array('uid', 'rate', 'topic_id'),
                        'str' => array('comment', 'comment_time', 'topic_name', 'username', 'icon', 'topic'),
                    ));
                    break;
                }
            }
        }
        return array($result, $all);
    }

    static public function pay($meet_id)
    {
        $meet = MeetDB::model()->findByPk($meet_id);
        if ($meet) {
            $now = date('Y-m-d H:i:s');
            $meet->status = Meet::USER_PAY;
            $meet->pay_time = $now;
            $meet->chat_time = $now;
            if ($meet->save()) {
                Yii::$app->redis->hmset('meet:' . $meet_id, array(
                    'status' => Meet::USER_PAY,
                    'pay_time' => $now,
                    'chat_time' => $now,
                ));
                $msg = new Msg($meet_id, 'after_pay');
                $msg->send();

                $meet = Meet::info($meet_id);
                Meet::setRun($meet_id, 'user', $meet['uid'], 1);
                Meet::setRun($meet_id, 'expert', $meet['expert_id'], 1);

//                Yii::$app->redis->zadd('user_chat:'.$meet->uid, time(), $meet_id);
//                Yii::$app->redis->zadd('user_chat:'.$expert['uid'], time(), $meet_id);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    static public function PrePay($uid, $meet_id)
    {
        $meet = Meet::info($meet_id);
        if ($meet['status'] != Meet::EXPERT_ACCEPT) {
            throw new ApiException(51, '预约状态不正确');
        }
        if ($p = PayLog::model()->findByAttributes(array('meet_id' => $meet_id, 'status' => array(1, 2)))) {
            return array($p->order_id, $p->price, $p->chat_id);
        }

        $order_sn = Logic::get_order_sn();
        $pay = new PayLog();
        $pay->order_id = $order_sn;
        $pay->meet_id = $meet_id;
        $pay->uid = $uid;
        $chat_id = Meet::createConversation(array(strval($uid)));
        $pay->chat_id = $chat_id;
        $pay->price = floatval($meet['price']);
        $pay->status = 1;
        $pay->save();

        return array($order_sn, $pay->price, $pay->chat_id);
    }

    static public function newToRun($meet_id, $type, $id)
    {
        $info = Meet::info($meet_id);
        if ($info['meet_type'] == Meet::TYPE_ASK) {
            return;
        }

        Yii::$app->redis->zdelete($type . '_meet:new:' . $id, $meet_id);
        Yii::$app->redis->zadd($type . '_meet:run:' . $id, time(), $meet_id);
    }

    static public function newToDone($meet_id, $type, $id)
    {
        $info = Meet::info($meet_id);
        if ($info['meet_type'] == Meet::TYPE_ASK) {
            return;
        }

        Yii::$app->redis->zdelete($type . '_meet:new:' . $id, $meet_id);
        Yii::$app->redis->zdelete($type . '_meet:new+run:' . $id, $meet_id);
        Yii::$app->redis->zadd($type . '_meet:done:' . $id, time(), $meet_id);
    }

    static public function runToDone($meet_id, $type, $id)
    {
        $info = Meet::info($meet_id);
        if ($info['meet_type'] == Meet::TYPE_ASK) {
            return;
        }

        Yii::$app->redis->zdelete($type . '_meet:run:' . $id, $meet_id);
        Yii::$app->redis->zdelete($type . '_meet:new+run:' . $id, $meet_id);
        Yii::$app->redis->zadd($type . '_meet:done:' . $id, time(), $meet_id);
    }

    static public function doneToRun($meet_id, $type, $id)
    {
        $info = Meet::info($meet_id);
        if ($info['meet_type'] == Meet::TYPE_ASK) {
            return;
        }

        Yii::$app->redis->zdelete($type . '_meet:done:' . $id, $meet_id);
        Yii::$app->redis->zadd($type . '_meet:run:' . $id, time(), $meet_id);
        //Yii::$app->redis->zadd($type.'_meet:new+run:'.$id, time(), $meet_id);
    }

    static public function setRun($meet_id, $type, $id, $remind = 0)
    {
        $info = Meet::info($meet_id);
        if ($info['meet_type'] == Meet::TYPE_ASK) {
            return;
        }

        if ($type == 'expert') {
            $k = 'new+run';
        } else {
            $k = 'run';
        }
        $time = time();
        if ($remind) {
            $time *= 2;
        }
        Yii::$app->redis->zadd($type . "_meet:$k:" . $id, $time, $meet_id);
    }

    static public function getUnread($uid)
    {
        $r = Logic::request('https://leancloud.cn/1.1/rtm/messages/unread/' . $uid, array(), array(
            'X-AVOSCloud-Application-Id: ' . Yii::app()->params['lean_cloud_id'],
            'X-AVOSCloud-Application-Key: ' . Yii::app()->params['lean_cloud_key'],
        ));
        if ($r) {
            $a = json_decode($r);
            return $a->count;
        } else {
            return 0;
        }
    }

    static public function createConversation($uids)
    {
        $r = Logic::request('https://api.leancloud.cn/1.1/classes/_Conversation',
            json_encode(array('m' => $uids)),
            array(
                'X-LC-Id: ' . Yii::app()->params['lean_cloud_id'],
                'X-LC-Key: ' . Yii::app()->params['lean_cloud_key'],
                'Content-Type: application/json',
            ));
        if ($r) {
            $data = json_decode($r, true);
            if (isset($data['objectId'])) {
                return $data['objectId'];
            } else {
                return false;
            }
        }
    }

    static public function getLastMsg($chat_id)
    {
        $r = Logic::request('https://leancloud.cn/1.1/rtm/messages/logs/?convid=' . $chat_id . '&limit=1', array(), array(
            'X-AVOSCloud-Application-Id: ' . Yii::app()->params['lean_cloud_id'],
            'X-AVOSCloud-Application-Key: ' . Yii::app()->params['lean_cloud_key'],
        ));
        if ($r) {
            $data = json_decode($r, true);
            if (isset($data[0])) {
                return array($data[0]['data'], $data[0]['timestamp']);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    static public function check($uid, $expert_id, $meet_type)
    {
//        if (!self::checkMeetValid($uid, $expert_id)) {
//            throw new ApiException(62, '约见未结束时不能再次提交预约');
//        }

        $user = User::info($uid);
        if ($user['expert'] == $expert_id) {
            throw new ApiException(63, '大咖不可以约见自己');
        }

        $platform = Yii::app()->request->getParam('platform');
        $ver = Yii::app()->request->getParam('ver');
        if (!($platform == 2 && version_compare($ver, '1.4') < 0)) {
            if (strstr($user['icon'], 'default')) {
                throw new ApiException(64, '推荐使用真实头像，让大咖对您有一个直观的认识，可以提高约见成功率哦！');
            }
        }

//        if ($meet_type == 2) {
//            if (!self::checkPeriodValid($expert_id)) {
//                throw new ApiException(66, '该大咖无法继续接受顾问邀请');
//            }
//        }

//        if (!$user['intro']) {
//            throw new ApiException(65, '请填写个人介绍');
//        }
    }

    // TODO: maybe better way
    static public function checkMeetValid($uid, $expert_id)
    {
        $list = Yii::$app->redis->zrange('user_meet:' . $uid, 0, -1);
        foreach ($list as $meet_id) {
            $info = Meet::info($meet_id);
            $status = array(Meet::EXPERT_REFUSE, Meet::EXPERT_TIMEOUT, Meet::USER_PAY_TIMEOUT, Meet::COMMENT,
                Meet::USER_CANCEL, Meet::AUDIT_REFUSE, Meet::ADMIN_CANCEL);
            if (!in_array($info['status'], $status) && $info['expert_id'] == $expert_id) {
                return false;
            }
        }
        return true;
    }

    static public function checkPeriodValid($expert_id)
    {
//        if (Yii::$app->redis->get('expert_period:' . $expert_id . ':' . date('Ym'))) {
//            return false;
//        }
        return true;

//        $list = Yii::$app->redis->zrange('expert_meet:'.$expert_id, 0, -1);
//        foreach ($list as $meet_id) {
//            $info = Meet::info($meet_id);
//            $status = array(Meet::EXPERT_REFUSE, Meet::EXPERT_TIMEOUT, Meet::USER_PAY_TIMEOUT, Meet::COMMENT,
//                Meet::USER_CANCEL, Meet::AUDIT_REFUSE, Meet::ADMIN_CANCEL, Meet::MEET);
//            if (!in_array($info['status'], $status) && $info['meet_type'] == 2) {
//                return false;
//            }
//        }
//        return true;
    }

    static public function accept($uid, $meet_id, $choose, $reason = '')
    {
        $meet = Meet::info($meet_id);
        if ($meet['status'] == Meet::USER_CANCEL) {
            throw new ApiException(52, '预约已被对方取消');
        }
        if ($meet['status'] != Meet::AUDIT_ACCEPT && $meet['status'] != Meet::CREATE) {
            throw new ApiException(51, '预约状态不正确');
        }
        $expert = Expert::info($meet['expert_id']);
        if ($expert['uid'] != $uid) {
            throw new ApiException(ApiException::NO_RIGHT);
        }
        if ($choose == 1) {
            Meet::newToRun($meet_id, 'expert', $meet['expert_id']);
            if ($meet['user_price'] == 0) {
                $status = Meet::USER_PAY;
                Meet::setRun($meet_id, 'expert', $meet['expert_id'], 1);
            } else {
                $status = Meet::EXPERT_ACCEPT;
                Meet::setRun($meet_id, 'expert', $meet['expert_id']);
            }
            Meet::setRun($meet_id, 'user', $meet['uid'], 1);
        } elseif ($choose == 0) {
            $status = Meet::EXPERT_REFUSE;
            if ($meet['meet_type'] == Meet::TYPE_ASK) {
                MeetAsk::setList('user', $meet['uid'], $meet_id, 1);
                MeetAsk::setList('expert', $meet['expert_id'], $meet_id, 1);
            } else {
                Meet::runToDone($meet_id, 'user', $meet['uid']);
                Meet::newToDone($meet_id, 'expert', $meet['expert_id']);
            }
        } else {
            throw new ApiException(ApiException::WRONG_PARAM);
        }

        $now = date('Y-m-d H:i:s');
        $param = array(
            'status' => $status,
            'confirm_time' => $now,
        );
        if ($choose == 0) {
            $param['refuse_reason'] = $reason;
        }
        if ($meet['user_price'] == 0 && $choose == 1) {
            $param['pay_time'] = $now;
        }
        Yii::$app->redis->hmset('meet:' . $meet_id, $param);

        MeetDB::model()->updateByPk($meet_id, $param);
        if ($choose == 1) {
            if ($meet['user_price'] == 0) {
                $msg = new Msg($meet_id, 'after_accept_without_pay');
            } else {
                $msg = new Msg($meet_id, 'after_accept');
            }
        } else {
            if ($meet['meet_type'] == Meet::TYPE_ASK) {
                $action = 'after_refuse_ask';
            } else {
                $action = 'after_refuse';
            }
            $msg = new Msg($meet_id, $action, array('reason' => $reason));
        }
        $msg->send();
    }

    public static function change($meet)
    {
        $meet['meet_id'] = $meet['show_id'];
        unset($meet['show_id']);
        return $meet;
    }

    public static function backShowId($show_id)
    {
        if ($e = MeetDB::model()->findByAttributes(['show_id' => $show_id])) {
            return $e->meet_id;
        }
        return $show_id;
    }

    public static function checkAskHeard($meet_id)
    {
        $unread = MeetAsk::model()->countByAttributes(['meet_id' => $meet_id, 'listened' => 0]);
        if ($unread > 0) {
            return false;
        } else {
            return true;
        }
    }
}