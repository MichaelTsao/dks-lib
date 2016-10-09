<?php
namespace mycompany\hangjiaapi\common;

use Yii;
use yii\base\Object;
use yii\base\Component;
use yii\console\Controller;
use mycompany\hangjiaapi\models;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/9/5
 * Time: 上午12:02
 */
class TestCommand extends Controller
{
    public function actionIndex()
    {
        var_dump(Expert::getPoolList(2));
    }

    public function actionPay($meet_id)
    {
        Meet::pay($meet_id);
    }

    public function actionAccept($meet_id, $choose)
    {
        $meet = Meet::info($meet_id);
        if ($choose == 1) {
            $status = Meet::EXPERT_ACCEPT;
            //Meet::newToRun($meet_id, 'user', $meet['uid']);
            Meet::newToRun($meet_id, 'expert', $meet['expert_id']);
        } elseif ($choose == 0) {
            $status = Meet::EXPERT_REFUSE;
            Meet::runToDone($meet_id, 'user', $meet['uid']);
            Meet::newToDone($meet_id, 'expert', $meet['expert_id']);
        } else {
            throw new ApiException(ApiException::WRONG_PARAM);
        }

        $now = date('Y-m-d H:i:s');
        Yii::app()->redis->getClient()->hMSet('meet:' . $meet_id, array(
            'status' => $status,
            'confirm_time' => $now,
        ));
        MeetDB::model()->updateByPk($meet_id, array('confirm_time' => $now, 'status' => $status));
    }

    public function actionLastMsg($chat_id)
    {
        $r = Logic::request('https://leancloud.cn/1.1/rtm/messages/logs?convid=' . $chat_id, array(), array(
            'X-LC-Id: ' . Yii::app()->params['lean_cloud_id'],
            'X-LC-Key: ' . Yii::app()->params['lean_cloud_master'] . ',master',
        ));
        if ($r) {
            $data = json_decode($r, true);
            var_dump($data);
        }
    }

    public function actionChat()
    {
        $r = Logic::request('https://api.leancloud.cn/1.1/classes/_Conversation',
            json_encode(array('m' => array(1, 2))),
            array(
                'X-LC-Id: ' . Yii::app()->params['lean_cloud_id'],
                'X-LC-Key: ' . Yii::app()->params['lean_cloud_key'],
                'Content-Type: application/json',
            ));
        var_dump($r);
    }

    public function actionRepairChat()
    {
        $data = MeetDB::model()->findAll();
        foreach ($data as $item) {
            if ($item->chat_id) {
                continue;
            }
            $expert = Expert::info($item->expert_id);
            $item->chat_id = Meet::createConversation(array($item->uid, $expert['uid']));
            $item->save();
            echo implode('|', array($item->meet_id, $item->chat_id)) . "\n";
        }
    }

    public function actionRepairChatMsg()
    {
        $data = MeetDB::model()->findAll();
        foreach ($data as $item) {
            if ($item->last_msg || !$item->chat_id || ($item->status != Meet::USER_PAY && $item->status != Meet::MEET && $item->status != Meet::COMMENT)) {
                continue;
            }
            $r = Meet::getLastMsg($item->chat_id);
            if ($r) {
                list($msg, $time) = $r;
                $item->last_msg = $msg;
                $item->last_msg_time = date('Y-m-d H:i:s', $time / 1000);
                $item->save();
                echo implode('|', array($item->meet_id, $item->last_msg, $item->last_msg_time)) . "\n";
            }
        }
    }

    public function actionRInfo()
    {
        $data = ExpertDB::model()->findAll();
        foreach ($data as $item) {
            UserDB::model()->updateByPk($item->uid, array('realname' => $item->name, 'intro' => $item->full_intro));
        }
    }

//    public function actionSetChat($meet_id){
//        $now = date('Y-m-d H:i:s');
//        $data = array(
//            'status' => Meet::CHAT,
//            'chat_time' => $now,
//        );
//        Yii::app()->redis->getClient()->hMSet('meet:'.$meet_id, $data);
//        MeetDB::model()->updateByPk($meet_id, $data);
//        $meet = Meet::info($meet_id);
//
//        Meet::setRun($meet_id, 'user', $meet['uid']);
//        Meet::setRun($meet_id, 'expert', $meet['expert_id'], 1);
//    }

    public function actionSetMeet($meet_id)
    {
        $meet = Meet::info($meet_id);
        $now = date('Y-m-d H:i:s');
        Yii::app()->redis->getClient()->hMSet('meet:' . $meet_id, array(
            'status' => Meet::MEET,
            'meet_time' => $now,
        ));
        Yii::app()->redis->getClient()->hIncrBy('expert:' . $meet['expert_id'], 'meet_people', 1);
        $hours = $meet['minutes'] / 60;
        $new_hours = Yii::app()->redis->getClient()->hIncrByFloat('expert:' . $meet['expert_id'], 'hours', $hours);
        Yii::app()->redis->getClient()->zIncrBy('expert_longtime', $hours, $meet['expert_id']);
        Yii::app()->redis->getClient()->zIncrBy('expert_active', 1, $meet['expert_id']);
        MeetDB::model()->updateByPk($meet_id, array('status' => Meet::MEET, 'meet_time' => $now));
        ExpertDB::model()->updateByPk($meet['expert_id'], array('hours' => $new_hours));
        $msg = new Msg($meet_id, 'after_confirm');
        $msg->send();

        Meet::setRun($meet_id, 'user', $meet['uid'], 1);
        Meet::setRun($meet_id, 'expert', $meet['expert_id']);
    }

    public function actionPush($id)
    {
        $data = [
            'first' => '老王,你好:',
            'id' => 123,
            'status' => 'aaa',
            'content' => "小李向您提出预约申请。\n请您在收到短信后48小时内登录“大咖说”处理预约，超时未处理该预约将自动取消",
        ];
        Logic::weixinPush($id, $data, 'http://m.dakashuo.com');
    }

    public function actionRepairComment($meet_id)
    {
        $info = Meet::info($meet_id);
        $data = array(
            'status' => Meet::MEET,
            'comment' => '',
            'rate' => null,
            'comment_time' => null,
        );
        MeetDB::model()->updateByPk($meet_id, $data);
        //Yii::app()->redis->getClient()->hMSet('meet:'.$meet_id, $data);
        //Meet::doneToRun($meet_id, 'user', $info['uid']);
    }

    public function actionMsg()
    {
        $msg = new Msg(1492, 'after_refuse', ['reason' => '哎呦喂']);
//        $msg->send();
        $msg->showAll();
        echo '欢迎来到大咖说，大咖说是创业公司的私人顾问。在这里，你的公司可以约见大咖进行单次咨询和长期指导帮助，有问题请联系大咖说客服。' .
            '（客服电话400-9697-169 /微信服务号：大咖说）请勿回复本短信';
        echo "\n\n";
        echo '本次验证码是XXX（10分钟内有效），请尽快完成验证。（客服电话400-9697-169 /微信服务号：大咖说）请勿回复本短信';
        echo "\n\n";
        echo "尊敬的XXX您好，您于XXX修改了大咖说的收款账号，如非您本人操作，请立即与客服联系（客服电话400-9697-169 /微信服务号：大咖说）请勿回复本短信";
        echo "\n\n";
    }

    public function actionDelKeys($key)
    {
        Yii::app()->redis->getClient()->eval("return redis.call('del', unpack(redis.call('keys', 'hj.$key:*')))");
    }

    public function actionTest($id)
    {
        $union = new UnionPay();
        $union->checkPayout($id);
//        var_dump($union->payout('6222000200129648135', '曹翔', 2, '测试', Logic::getOrderId()));
//        var_dump($union->payout('6226200105300512', '龚成光', 1, '测试'));
        //$union->refund($id, $price);
//        $union->payout('6222000200129648135', '曹翔', '110105198002039435', 0.02);
//        $data = [
//            [
//                'id' => '2016081821001004180244853441',
//                'money' => 0.01,
//                'reason' => '取消服务'
//            ]
//        ];
//        $alipay = new AlipaySDK();
//        echo $alipay->refund($data)."\n";
//        return;

//        $data = [
//            [
//                'account' => 'caoxiang@yeah.net',
//                'name' => '曹翔',
//                'money' => '0.02',
//                'remark' => 'test',
//            ]
//        ];
//        $alipay = new AlipaySDK();
//        echo $alipay->trans($data) . "\n";
    }

    public function actionSetShowID()
    {
        $expert = ExpertDB::model()->findAll();
        foreach ($expert as $item) {
            $item->show_id = Logic::getId();
            $item->save();
        }

        $meet = MeetDB::model()->findAll();
        foreach ($meet as $item) {
            $item->show_id = Logic::getOrderId();
            $item->save();
        }
    }
}