<?php
namespace mycompany\hangjiaapi\common;

use Yii;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/5/6
 * Time: 下午3:01
 */

class Server {
    protected $uid;
    protected $meet_id;
    protected $conn_id;

    public function __construct($uid, $meet_id, $conn_id){
        $this->uid = $uid;
        $this->meet_id = $meet_id;
        $this->conn_id = $conn_id;
    }

    public function enterRoom($position, $source, $version, $market, $device){
        Yii::app()->redis->getClient()->set("user_conn:$this->meet_id:$this->uid:$this->conn_id", time());

        /*
        Yii::app()->redis->getClient()->hSet("user_conn_info:$this->meet_id:$this->uid:$this->conn_id", 'source', $source);
        Yii::app()->redis->getClient()->hSet("user_conn_info:$this->meet_id:$this->uid:$this->conn_id", 'version', $version);
        Yii::app()->redis->getClient()->hSet("user_conn_info:$this->meet_id:$this->uid:$this->conn_id", 'market', $market);
        Yii::app()->redis->getClient()->hSet("user_conn_info:$this->meet_id:$this->uid:$this->conn_id", 'device', $device);
        */

        if ($position < 0) {
            $position = 0;
        }
        $key = 'meet_chat:'.$this->meet_id;
        $msg = Yii::app()->redis->getClient()->lRange($key, $position, -1);
        if (!$msg) {
            $msg = array();
        }
        list($pid, $r) = self::makeEnterRoomPkg($this->uid, $this->conn_id, $msg, $this->meet_id, 1, 1);
        Logic::server_request(Yii::app()->params['cserver_ip'], Yii::app()->params['cserver_port'], $pid, $r);
        Yii::app()->redis->getClient()->set("meet_chat_unread:".$this->meet_id.":".$this->uid, 0);

        return 0;
    }

    public function leaveRoom(){
        Yii::app()->redis->getClient()->del("user_conn:$this->meet_id:$this->uid:$this->conn_id");
    }

    public function chat($to_uid, $message, $secret){
        //$message = Logic::strBanned($message, true);
        if (!$message) {
            return 1;
        }
        try {
            $meet_info = Meet::info($this->meet_id);
        } catch (ApiException $e) {
            return 2;
        }
        try {
            $expert_info = Expert::info($meet_info['expert_id']);
        } catch (ApiException $e) {
            return 3;
        }
        if ($this->uid == $meet_info['uid']) {
            $to_uid = $expert_info['uid'];
        } elseif ($this->uid == $expert_info['uid']) {
            $to_uid = $meet_info['uid'];
        } else {
            return 4;
        }
        $conn = Yii::app()->redis->getClient()->keys("user_conn:$this->meet_id:$to_uid:*");
        if (!$conn) {
            Yii::app()->redis->getClient()->incr("meet_chat_unread:".$this->meet_id.":".$to_uid);
            Logic::sendPush();
        }else{
            foreach ($conn as $c) {
                $tmp = explode(':', $c);
                $conn_id = $tmp[3];
                $from_info = $this->uid;
                $to_info = $to_uid;
                list($pid, $data) = self::makeChatPkg($to_uid, $conn_id, $this->meet_id, $message, $secret, $from_info, $to_info);
                Logic::server_request(Yii::app()->params['cserver_ip'], Yii::app()->params['cserver_port'], $pid, $data);
            }
        }
        Yii::app()->redis->getClient()->rPush('meet_chat:'.$this->meet_id, implode('|', array($this->uid, time(), $message)));

        return 0;
    }

    public function setMute($to_uid, $seconds){
        if ($seconds <= 0 || !$this->meet_id || !$this->uid || !$to_uid) {
            return 1;
        }

        $time = time() + $seconds;
        $key = "room_mute:$this->meet_id:$to_uid";
        Yii::app()->redis->getClient()->setex($key, $seconds, $time);

        self::noticeOthers('mute', $this->meet_id, array('from_uid'=>$this->uid, 'conn_id'=>$this->conn_id, 'to_uid'=>$to_uid, 'status'=>0));

        $sql = "INSERT IGNORE INTO c_room_chatdisable_list (uid, meet_id, expire) VALUES ('$this->uid', '$this->meet_id', '$time')";
        Yii::app()->db->createCommand($sql)->execute();

        return 0;
    }

    public function setAdmin($to_uid, $is_admin){
        if (!$this->meet_id || !$to_uid) {
            return 1;
        }
        // TODO: check right of from user
        $param = array('uid'=>$to_uid, 'meet_id'=>$this->meet_id, 'type'=>3);
        if ($is_admin) {
            $admin = CRoomLimits::model()->findByAttributes($param);
            if (!$admin) {
                $admin = new CRoomLimits();
                $admin->uid = $to_uid;
                $admin->meet_id = $this->meet_id;
                $admin->type = 3;
                $admin->save();
                Yii::app()->redis->getClient()->set("room_limit:$this->meet_id:$to_uid", 3);
            }
        }else{
            CRoomLimits::model()->deleteAllByAttributes($param);
            $i = Yii::app()->redis->getClient()->get("room_limit:$this->meet_id:$to_uid");
            if ($i && $i == 3) {
                Yii::app()->redis->getClient()->del("room_limit:$this->meet_id:$to_uid");
            }
        }
        $conns = Yii::app()->redis->getClient()->sMembers("room_user_conn:$this->meet_id:$to_uid");
        if ($conns) {
            $from_info = Room::getUserInfo($this->meet_id, $this->uid, $this->conn_id);
            $to_info = Room::getUserInfo($this->meet_id, $to_uid, 0);
            foreach ($conns as $c) {
                $r = array(
                    'uid'=>$to_uid,
                    'meet_id'=>$this->meet_id,
                    'conn_id'=>$c,
                    'from_info'=>$from_info,
                    'to_info'=>$to_info,
                    'is_admin'=>$is_admin,
                );
                Logic::server_request(Yii::app()->params['cserver_ip'], Yii::app()->params['cserver_port'], 52, $r);
            }
        }
        return 0;
    }

    public function setFav($type){
        list($r, $d) = Room::setFav($this->uid, $this->meet_id, $type);
        if ($r != 0) {
            return $r;
        }
        self::noticeOthers('fav', $this->meet_id, array('count'=>$d));
        return 0;
    }

    public function kickAway($to_uid){
        self::noticeOthers('kick_away', $this->meet_id, array('from_uid'=>$this->uid, 'to_uid'=>$to_uid, 'conn_id'=>$this->conn_id));
        return 0;
    }

    static public function makeEnterRoomPkg($to_uid, $conn_id, $meet_id, $msg){
        $pid = 21;
        $r = array('uid'=>$to_uid, 'meet_id'=>$meet_id, 'msg'=>$msg, 'conn_id'=>$conn_id, 'result'=>0);
        return array($pid, $r);
    }

    static public function makeChatPkg($to_uid, $conn_id, $meet_id, $msg, $secret, $from_info, $to_info){
        $r = array('uid'=>$to_uid, 'meet_id'=>$meet_id, 'conn_id'=>$conn_id, 'msg'=>$msg, 'secret'=>$secret,
            'from_info'=>$from_info, 'to_info'=>$to_info);
        return array(32, $r);
    }

    static public function makeMutePkg($uid, $conn_id, $meet_id, $from_info, $to_info, $status){
        $r = array('uid'=>$uid, 'meet_id'=>$meet_id, 'conn_id'=>$conn_id, 'enabled'=>$status,
            'to_info'=>$to_info, 'from_info'=>$from_info);
        return array(62, $r);
    }

    static public function makeKickAwayPkg($uid, $conn_id, $meet_id, $to_uid, $to_info){
        $r = array('uid'=>$uid, 'meet_id'=>$meet_id, 'conn_id'=>$conn_id, 'to_info'=>$to_info, 'to_uid'=>$to_uid);
        return array(72, $r);
    }

    static public function makeFavPkg($uid, $conn_id, $meet_id, $count){
        $r = array('uid'=>$uid, 'meet_id'=>$meet_id, 'conn_id'=>$conn_id, 'count'=>$count);
        return array(26, $r);
    }

    public function sendResult($pid, $r, $param=''){
        $result['meet_id'] = $this->meet_id;
        $result['uid'] = $this->uid;
        $result['conn_id'] = $this->conn_id;
        $result['result'] = $r;
        if ($param) {
            foreach ($param as $key => $value) {
                $result[$key] = $value;
            }
        }
        Logic::server_request(Yii::app()->params['cserver_ip'], Yii::app()->params['cserver_port'], $pid, $result);
    }
}