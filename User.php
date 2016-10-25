<?php
namespace mycompany\common;

use mycompany\hangjiaapi\models\UserDB;
use Yii;
use yii\redis\Cache;
use yii\redis\Connection;
use mycompany\business;
/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 15/8/11
 * Time: 下午6:11
 */
class User
{
    const LOGIN_TYPE_WEIXIN = 1;
    const LOGIN_TYPE_FENGYUN = 2;
    public static $loginTypes = [
        self::LOGIN_TYPE_WEIXIN,
        self::LOGIN_TYPE_FENGYUN,
    ];

    static public function auth($token){
        if (!$token) {
            throw new ApiException(ApiException::TOKEN_FAIL);
        }
        $key = 'token:'.$token;
        $value = Yii::$app->redis->get($key);
        if (!$value) {
            $info = business\UserToken::findOne(['token'=>$token]);
            if ($info) {
                $ctime = strtotime($info->ctime);
                $last_time = $ctime + 86400 * 30 - time();
                if ($last_time > 0) {
                    $value = $ctime.'|'.$info->uid;
                    Yii::$app->redis->setex($key, $last_time, $value);
                }
            }
        }
        if (!$value) {
            throw new ApiException(ApiException::TOKEN_FAIL);
        }
        list($ctime, $uid) = explode('|', $value);
        if (time() - $ctime >= 86400 * 30) {
            throw new ApiException(ApiException::TOKEN_FAIL);
        }
        return $uid;
    }

    static public function makeToken($uid){
        return md5(time().$uid.rand(100,999));
    }

    static public function login($phone, $password, $device_id='', $platform=0){
        $user = business\UserDB::findOne(['phone'=>$phone, 'password'=>md5($password)]);
        //$user = UserDB::model()->findByAttributes(array('phone'=>$phone, 'password'=>md5($password)));
        if (!$user) {
            throw new ApiException(ApiException::LOGIN_FAIL);
        }
        if ($user->status != 1) {
            throw new ApiException(ApiException::USER_CLOSED);
        }
        $token = self::makeToken($user->uid);
        $token_info = new business\UserToken();
        $token_info->uid = $user->uid;
        $token_info->token = $token;
        $token_info->save();

        $user->login_time = date('Y-m-d H:i:s');
        $user->save();

        Yii::$app->db->createCommand()->insert('login_log', [
            'uid' => $user->uid,
            'platform' => $platform,
        ])->execute();

        business\DeviceUser::create($user->uid, $device_id, $platform);

        $info = self::info($user->uid);
        $r = [
            'uid'=>intval($user->uid),
            'token'=>strval($token)
        ];
        return $r+$info;
    }

    static public function info($uid, $is_expert=false){
        if ($is_expert) {
            $e = business\ExpertDB::findOne(['expert_id'=>$uid]);
            $uid = $e->uid;
        }
        $key = 'user:'.$uid;
        $info = Yii::$app->redis->hgetall($key);
        if (!$info || count($info) != 10) {
            $data = business\UserDB::findOne($uid)->toArray();
            if ($data) {
                $info = [];
                $info['uid'] = intval($data['uid']);
                $info['icon'] = Logic::imagePath($data['icon'], 'icon');
                $info['realname'] = strval($data['realname']);
                $info['gender'] = intval($data['gender']);
                $info['phone'] = strval($data['phone']);
                $info['intro'] = strval($data['intro']);
                $info['title'] = strval($data['title']);
                $info['company'] = strval($data['company']);

                $expert = business\ExpertDB::findOne(['uid'=>$uid]);
                if ($expert) {
                    $info['expert'] = intval($expert->expert_id);
                    $info['access'] = intval($expert->access_status);
                }else{
                    $info['expert'] = 0;
                    $info['access'] = -1;
                }
            }else{
                throw new ApiException(ApiException::USER_NOT_EXIST);
            }
            RedisCommon::setHash_Array($key, $info);
        }
        $info['uid'] = intval($info['uid']);
        $info['gender'] = intval($info['gender']);
        $info['expert'] = intval($info['expert']);
        $info['access'] = intval($info['access']);
        if ($info['expert']) {
            $expert_info = Expert::info($info['expert'], false);
            $info['expert_info'] = Expert::change($expert_info);
        }else{
            $info['expert_info'] = [];
        }
        return $info;
    }

    static public function expertID($uid){
        $user = self::info($uid);
        if (!$user['expert']) {
            throw new ApiException(ApiException::USER_NOT_EXPERT);
        }
        return $user['expert'];
    }

    static public function setIcon($uid, $file){
        $info = User::info($uid);
        if (!$file) {
            return 1;
        }
        $file = '/images/icon/'.$file;
        $img_url = Yii::$app->params['img_host'].$file;
        User::setCache($uid, 'icon', $img_url);
        if ($info['expert']) {
            Yii::$app->redis->hset('expert:'.$info['expert'], 'icon', $img_url);
        }
        Yii::$app->db->createCommand()->update('user',['icon'=>$file], 'uid='.$uid)->execute();
        return 0;
    }

    static public function setCache($uid, $key, $value){
        return Yii::$app->redis->hset('user:'.$uid, $key, $value);
    }

    static public function update($uid, $type, $info){
        if ($type == 'name') {
            $show = '名字';
            $key = 'realname';
            $error = 51;
        }elseif ($type == 'gender'){
            $show = '性别';
            $key = 'gender';
            $error = 52;
        }elseif ($type == 'access') {
            $show = '单次预约设置';
            $key = 'access_status';
            $error = 53;
        }elseif ($type == 'intro') {
            $show = '个人介绍';
            $key = 'intro';
            $error = 54;
        }elseif ($type == 'title') {
            $show = '职位';
            $key = 'title';
            $error = 55;
        }elseif ($type == 'company') {
            $show = '公司名';
            $key = 'company';
            $error = 56;
        }elseif ($type == 'period_access') {
            $show = '长期预约设置';
            $key = 'period_status';
            $error = 57;
        }elseif ($type == 'lesson_access') {
            $show = '课程预约设置';
            $key = 'lesson_status';
            $error = 58;
        }elseif ($type == 'ask_access') {
            $show = '问答预约设置';
            $key = 'ask_status';
            $error = 59;
        }else{
            throw new ApiException(ApiException::WRONG_PARAM);
        }
        if ($type == 'intro') {
            $len = mb_strlen($info, 'utf-8');
            if ($len < 30) {
                throw new ApiException(55, '个人介绍不能少于30个字');
            }
            if ($len > 1000) {
                throw new ApiException(56, '个人介绍不能多于1000个字');
            }
        }
        if (($type != 'access' && $type != 'period_access' && $type != 'lesson_access' && $type != 'ask_access' && !$info)
            || ($type == 'access' && !in_array($info, [0, 1]))
            || ($type == 'period_access' && !in_array($info, [0, 1]))
            || ($type == 'lesson_access' && !in_array($info, [0, 1]))
            || ($type == 'ask_access' && !in_array($info, [0, 1]))
        ) {
            throw new ApiException($error, $show.'填写错误');
        }

        if ($type == 'access' || $type == 'period_access' || $type == 'lesson_access' || $type == 'ask_access') {
            $expert_id = User::expertID($uid);
            Yii::$app->db->createCommand()->update('expert',[$key=>$info], 'expert_id='.$expert_id)->execute();
            Yii::$app->redis->hset('expert:'.$expert_id, $key, $info);
            $type == 'access' && Yii::$app->redis->hset('user:'.$uid, 'access', $info);
        }else{
            Yii::$app->db->createCommand()->update('user',[$key=>$info], 'uid='.$uid)->execute();
            Yii::$app->redis->hset('user:'.$uid, $key, $info);
        }
    }

    public static function fengyunLogin($token, $expert_id)
    {
        $key = '201609090919demo';
        $security = '201609090919demo';

        $expert = Expert::info($expert_id);
        $params = [
            'key' => $key,
            'security' => $security,
            'token' => $token,
            'resourceId' => $expert_id,
            'resourceName' => $expert['name'],
        ];
        $url = 'http://demo.chuangxin360.com:8090/api/userauth/decryptTokenRecord';
        $r = Logic::request($url, $params);
        Yii::log('fengyun: ' . $r, 'warning');
        $result = json_decode($r, true);
        if (isset($result['code']) && $result['code'] == 200 && isset($result['data'])) {
            return $result['data'];
        } else {
            return false;
        }
    }
}