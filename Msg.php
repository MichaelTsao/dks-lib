<?php
namespace mycompany\common;

use Yii;
//use yii\db\ActiveQuery;
//use yii\db\ActiveRecord;
//use yii\db\QueryBuilder;
//use yii\data\ActiveDataProvider;//活动记录
use yii\base\Object;
use yii\base\Component;
use mycompany\business;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 16/1/21
 * Time: 上午11:32
 */
class Msg extends Component
{
    private $meet;
    private $type;
    private $param;
    private $_msg = null;
    private $_shortMsg = null;
    private $_kefuMsg = null;
    private $_phone = null;
    private $_role = null;
    private $_weixinData = null;

    private $keys = ['{USER_NAME}', '{EXPERT_NAME}', '{NOW}', '{REASON}', '{DATE}', '{MEET_ID}', '{FROM_ROLE}',
        '{USER_COMPANY}', '{USER_TITLE}'];

    private $full_msg = [
        'after_accept' => "大咖{EXPERT_NAME}在{NOW}接受了您的预约。\n请在15天内登录“大咖说”支付费用，超时未支付该预约将自动取消",
        'after_accept_without_pay' => "大咖{EXPERT_NAME}在{NOW}接受您的预约。\n请及时登录“大咖说”和大咖确认预约事宜",
        'after_refuse' => "大咖{EXPERT_NAME}在{NOW}关闭了您所申请的预约。\n您可以登录“大咖说”重新预约，或者预约其他大咖来获得帮助",
        'after_refuse_ask' => "大咖{EXPERT_NAME}在{NOW}关闭了您提出的问题。\n您可以登录“大咖说”重新提问，或者请教其他大咖来获得帮助",
        'after_pay' => "{USER_COMPANY}{USER_TITLE}{USER_NAME}已向您支付了预约费用。\n请尽快登录“大咖说”联系TA并确认预约事宜",
        'after_user_chat' => "{USER_COMPANY}{USER_TITLE}{USER_NAME}在{NOW}发来联系信息。\n请尽快登录“大咖说”查看，方便您完成与TA的预约",
        'after_expert_chat' => "大咖{EXPERT_NAME}在{NOW}发来联系信息。\n请尽快登录“大咖说”查看，方便您完成与大咖的预约",
        'after_confirm' => "大咖{EXPERT_NAME}在{NOW}确认与您完成了预约。\n本次预约对您很有帮助吧！请登录“大咖说”给大咖发封感谢信表示谢意吧",
        'after_create' => '您的预约已提交。大咖会在一周内对本预约进行回应',
        'after_create_ask' => '您的问题已提交。大咖会在一周内对本问题进行回应',
        'after_answer' => '大咖{EXPERT_NAME}回答了您的问题。您可以登录“大咖说”收听',
    ];

    private $short_msg = [
        'after_accept' => "大咖{EXPERT_NAME}已接受预约, 请在15天内支付费用",
        'after_accept_without_pay' => "大咖{EXPERT_NAME}已接受预约",
        'after_refuse' => "您对大咖{EXPERT_NAME}的预约已被关闭",
        'after_refuse_ask' => "您对大咖{EXPERT_NAME}提出的问题已被关闭",
        'after_pay' => "{USER_COMPANY}{USER_TITLE}{USER_NAME}已向您支付了预约费用",
        'after_user_chat' => "{USER_COMPANY}{USER_TITLE}{USER_NAME}发来联系信息, 请尽快回复",
        'after_expert_chat' => "大咖{EXPERT_NAME}发来联系信息, 请尽快回复",
        'after_confirm' => "大咖{EXPERT_NAME}已确认与您完成了预约。请致谢",
        'after_answer' => '大咖{EXPERT_NAME}回答了您的问题',
    ];

    private $kefu_msg = [
        'after_create' => '后台有新订单，请尽快审核。订单编号：{MEET_ID}，订单时间：{NOW} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}',
        'after_create_ask' => '后台有新问答订单，请尽快审核。订单编号：{MEET_ID}，订单时间：{NOW} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}',
        'after_cancel' => '订单编号：{MEET_ID} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}, 学员已取消，取消时间：{NOW}',
        'after_pay' => '订单编号：{MEET_ID} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}, 学员已付款，付款时间：{NOW}',
        'after_confirm' => '订单编号：{MEET_ID} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}, {FROM_ROLE}确认已完成，确认时间：{NOW}',
        'after_answer' => '订单编号：{MEET_ID} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}, 大咖已回答，回答时间：{NOW}',
        'after_accept' => '订单编号：{MEET_ID} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}, 大咖已接单，接单时间：{NOW}',
        'after_refuse' => '订单编号：{MEET_ID} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}, 大咖已拒绝，拒绝时间：{NOW}',
        'after_refuse_ask' => '订单编号：{MEET_ID} ,学员姓名：{USER_NAME}， 预约大咖：{EXPERT_NAME}, 大咖已拒绝，拒绝时间：{NOW}',
    ];

    private $to = [
        'after_accept' => 'user',
        'after_accept_without_pay' => 'user',
        'after_refuse' => 'user',
        'after_refuse_ask' => 'user',
        'after_pay' => 'expert',
        'after_user_chat' => 'expert',
        'after_expert_chat' => 'user',
        'after_confirm' => 'user',
        'after_create' => 'user',
        'after_create_ask' => 'user',
        'after_answer' => 'user',
    ];

    private $chat_scene = ['after_user_chat', 'after_expert_chat'];

    public function __construct($meet_id, $type, $param = [])
    {
        $this->meet = Meet::info($meet_id);
        $this->type = strtolower($type);
        $this->param = $param;
    }

    public function getUserName()
    {
        return User::info($this->meet['uid'])['realname'];
    }

    public function getUserCompany()
    {
        return User::info($this->meet['uid'])['company'];
    }

    public function getUserTitle()
    {
        return User::info($this->meet['uid'])['title'];
    }

    public function getExpertName()
    {
        return User::info(Expert::info($this->meet['expert_id'])['uid'])['realname'];
    }

    public function getScene()
    {
        if (in_array($this->type, $this->chat_scene)) {
            return 3;
        } else {
            return 1;
        }
    }

    public function getPhone()
    {
        if (is_null($this->_phone)) {
            if ($this->role == 1) {
                $this->_phone = User::info($this->meet['uid'])['phone'];
            } elseif ($this->role == 2) {
                $this->_phone = User::info(Expert::info($this->meet['expert_id'])['uid'])['phone'];
            } else {
                $this->_phone = '';
            }
        }
        return $this->_phone;
    }

    public function getRole()
    {
        if (is_null($this->_role)) {
            $this->_role = 0;
            if (isset($this->to[$this->type])) {
                if ($this->to[$this->type] == 'user') {
                    $this->_role = 1;
                } elseif ($this->to[$this->type] == 'expert') {
                    $this->_role = 2;
                }
            }
        }
        return $this->_role;
    }

    public function getNow()
    {
        return date('Y年m月d日 H时i分');
    }

    public function getReason()
    {
        if (isset($this->param['reason'])) {
            return $this->param['reason'];
        } else {
            return '';
        }
    }

    public function getDate()
    {
        if (isset($this->param['date'])) {
            return $this->param['date'];
        } else {
            return '';
        }
    }

    public function getFromRole()
    {
        if (isset($this->param['from'])) {
            return $this->param['from'] == 1 ? '用户' : '大咖';
        } else {
            return '';
        }
    }

    public function getMeetId()
    {
        return $this->meet['show_id'];
    }

    protected function replace(&$msg, $key, $fake = false)
    {
        if (strstr($msg, $key) !== false) {
            if ($fake) {
                $msg = str_replace($key, 'XXX', $msg);
            } else {
                $value_name = str_replace('_', '', lcfirst(ucwords(strtolower(substr($key, 1, -1)), '_')));
                $msg = str_replace($key, $this->$value_name, $msg);
            }
        }
    }

    public function getMsg()
    {
        if (is_null($this->_msg)) {
            $this->_msg = $this->makeMsg($this->full_msg);
        }
        return $this->_msg;
    }

    public function getShortMsg()
    {
        if (is_null($this->_shortMsg)) {
            $this->_shortMsg = $this->makeMsg($this->short_msg);
        }
        return $this->_shortMsg;
    }

    public function getKefuMsg()
    {
        if (is_null($this->_kefuMsg)) {
            $this->_kefuMsg = $this->makeMsg($this->kefu_msg);
        }
        return $this->_kefuMsg;
    }

    private function makeMsg($template)
    {
        if (!isset($template[$this->type])) {
            return '';
        }
        $msg = $template[$this->type];
        foreach ($this->keys as $key) {
            $this->replace($msg, $key);
        }
        return $msg;
    }

    public function getGreet()
    {
        if ($this->role == 1) {
            return $this->userName . '您好:';
        } elseif ($this->role == 2) {
            return $this->expertName . '大咖您好:';
        }
        return '';
    }

    public function getStatus()
    {
        if ($this->role == 1) {
            return $this->meet['user_status'];
        } elseif ($this->role == 2) {
            return $this->meet['expert_status'];
        }
        return '';
    }

    public function getWeixinData()
    {
        if (is_null($this->_weixinData)) {
            if ($this->msg) {
                $this->_weixinData = [
                    'first' => $this->greet,
                    'content' => $this->msg,
                    'id' => $this->meetId,
                    'status' => $this->status,
                ];
            } else {
                $this->_weixinData = [];
            }
        }
        return $this->_weixinData;
    }

    public function getWeixinUrl()
    {
        if ($this->to[$this->type] == 'user') {
            return Yii::$app->params['web_host'] . '/meet/' . $this->meet['meet_id'];
        } else {
            return Yii::$app->params['web_host'] . '/meet/expert/' . $this->meet['meet_id'];
        }
    }

    public function getDeviceId($type)
    {
        $id = [];

        if ($this->role == 1) {
            $uid = $this->meet['uid'];
        } elseif ($this->role == 2) {
            $uid = Expert::info($this->meet['expert_id'])['uid'];
        } else {
            return $id;
        }

        $data = business\DeviceUser::find()
            ->select(['device_id'])
            ->where(['uid' => $uid, 'type' => $type])
            ->all();
        foreach ($data as $item) {
            $id[] = $item->device_id;
        }
        return $id;
    }

    public function send()
    {
        $send = 1;
        if (isset($this->param['from']) && $this->param['from'] == $this->role) {
            $send = 0;
        }

        if ($send) {
            if ($this->msg && $this->phone) {
                Logic::sendSMS($this->phone, str_replace("\n", '', $this->msg));
            }

            if ($this->weixinData) {
                foreach ($this->getDeviceId(5) as $id) {
                    Logic::weixinPush($id, $this->weixinData, $this->weixinUrl);
                }
            }

            if ($this->shortMsg) {
                foreach ($this->getDeviceId(2) as $id) {
                    Logic::push($id, 2, $this->shortMsg, $this->meetId, $this->role, $this->scene);
                }
                foreach ($this->getDeviceId(3) as $id) {
                    Logic::push($id, 3, $this->shortMsg, $this->meetId, $this->role, $this->scene);
                }
            }
        }

        if ($this->kefuMsg && isset(Yii::$app->params['kefu_phone'])) {
            Logic::sendSMS(Yii::$app->params['kefu_phone'], str_replace("\n", '', $this->kefuMsg));
        }
    }

    public function showAll()
    {
        foreach ($this->full_msg as $msg) {
            foreach ($this->keys as $key) {
                $this->replace($msg, $key, true);
            }
            echo str_replace("\n", '', $msg) . "（客服电话400-9697-169 /微信服务号：大咖说）请勿回复本短信\n\n";
        }
        foreach ($this->kefu_msg as $msg) {
            foreach ($this->keys as $key) {
                $this->replace($msg, $key, true);
            }
            echo str_replace("\n", '', $msg) . "（客服电话400-9697-169 /微信服务号：大咖说）请勿回复本短信\n\n";
        }
    }
}