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
 * User: jinlin wang
 * Date: 2016/9/16
 * Time: 上午11:49
 */
class Expert
{
    static public function info($expert_id, $with_user = true)
    {
        $key = 'expert:' . $expert_id;
        $info = Yii::$app->redis->hgetall($key);
        if (!$info || count($info) != 28) {
            if (strlen(strval($expert_id)) == 10) {
                $info = business\ExpertDB::find()
                    ->where(['show_id' => $expert_id])
                    ->asArray()
                    ->one();
            } else {
                $info = business\ExpertDB::find()
                    ->where(['expert_id' => $expert_id])
                    ->asArray()
                    ->one();
            }
            if (!$info) {
                ApiException::Msgs(ApiException::EXPERT_NOT_EXIST);
            }
            $info['real_price'] = $info['price'];
            $info['price'] = $info['price'] * $info['meet_hour'];
            if (Yii::$app->params['price_type'] == 1) {
                $info['price'] = $info['price'] * (1 + Yii::$app->params['fee_rate']);
            }
            if ($info['meet_hour'] == intval($info['meet_hour'])) {
                $info['meet_hour'] = intval($info['meet_hour']);
            }
            $info['field'] = '';
            $info['cover'] = Logic::imagePath($info['cover'], 'cover');

            $info['meet_people'] = business\MeetDB::find()
                //->where(['expert_id' => $expert_id, 'type' => 1])
                //->where('in','status',[7,8])
                ->where('expert_id='.$expert_id.' AND type=1 AND status IN (7,8)')
                ->count();

            $info['want_people'] = business\UserFav::find()
                ->where(['expert_id' => $expert_id])
                ->count();
            //RedisCommon::setHash_Array($key, $info);
            RedisCommon::setHash_Array($key, $info);
        }
        $info['locations'] = self::getLocations($info['expert_id']);
        $info['location'] = implode('，', $info['locations']);

        $sql = "select f.name 
                from expert_field e, field f
                where expert_id=$expert_id and e.field_id=f.id
                order by e.sort";
        $fields = Yii::$app->db->createCommand($sql)->queryColumn();
        $brief = implode('，', $fields);
        $info['brief'] = $brief;

        $sql = "select id, lesson_name, lesson_price, lesson_hour, lesson_desc
                from expert_lesson
                where expert_id=$expert_id and lesson_status=1";
        $lessons = Yii::$app->db->createCommand($sql)->queryAll();
        $info['lesson'] = $lessons;

        if ($with_user) {
            $user_info = User::info($info['uid']);
            $info['icon'] = $user_info['icon'];
            $info['name'] = $user_info['realname'];
            $info['gender'] = $user_info['gender'];
        }

        $info['meet_people'] += Yii::$app->redis->get('expert_fake_meet:' . $expert_id);
        $info['want_people'] += Yii::$app->redis->get('expert_fake_fav:' . $expert_id);
        $info = Logic::formatDict($info, array(
            'int' => array('expert_id', 'uid', 'work_years', 'rate', 'status', 'meet_people',
                'want_people', 'access_status', 'hours'),
            'str' => array('brief'),
            'float' => array('hours', 'price'),
        ));

        if ($labels = Yii::$app->redis->zrange('expert_label:' . $expert_id, 0, -1)) {
            $info['labels_id'] = $labels;
            $label_name = [];
            $label_all = [];
            foreach ($labels as $l) {
                $sql = "select name, remark from adept_label where id=$l";
                $label_info = Yii::$app->db->createCommand($sql)->queryOne();
                $label_name[] = $label_info['name'];
                $label_all[] = $label_info;
            }
            $info['labels'] = $label_name;
            $info['labels_info'] = $label_all;
        } else {
            $info['labels_id'] = [];
            $info['labels'] = [];
            $info['labels_info'] = [];
        }


        $topic = self::topic($expert_id);
        if (!$topic) {
            $info['topic'] = [];
        } else {
            $info['topic'] = $topic;
        }

        list($comment, $count) = Meet::commentWithImg($expert_id);
        if (!$comment) {
            list($comment, $count) = Meet::comment($expert_id, 1, 1);
        }
        if (!$comment) {
            $comment = [];
        }
        $info['comment'] = $comment;
        $info['comment_count'] = $count;

        $meet_want = Yii::$app->redis->smembers('expert_meet_label:' . $expert_id);
        if (!$meet_want) {
            $meet_want = [];
        }
        $info['want_meet'] = $meet_want;

        if (Yii::$app->request->post('platform') == 5) {
            $sql = "select name from expert_label where expert_id=$expert_id";
            $labels = Yii::$app->db->createCommand($sql)->queryAll();
            $l = [];
            foreach ($labels as $label) {
                $l[] = $label['name'];
            }
            $info['label'] = implode(',', $l);
        }

        if (Meet::checkPeriodValid($expert_id)) {
            $info['period_valid'] = 1;
        } else {
            $info['period_valid'] = 0;
        }

        return $info;
    }

    static public function topic($expert_id, $n = 0)
    {
        $key = 'expert_topic:' . $expert_id;
        $info = Yii::$app->redis->zrevrange($key, 0, $n - 1);
        if (!$info) {
            return false;
        }
        $topics = [];
        foreach ($info as $one) {
            $topic_info = self::topicInfo($one);
            if (isset($topic_info['status']) && $topic_info['status'] == 1) {
                $topics[] = $topic_info;
            }
        }
        return $topics;
    }

    static public function topicInfo($topic_id)
    {
        $key = 'topic:' . $topic_id;
        $topic = Yii::$app->redis->hgetall($key);
        if (!$topic) {
            $data = business\TopicDB::findOne($topic_id);
            if ($data) {
                $topic = [];
                $topic['topic_id'] = $topic_id;
                $topic['name'] = $data->name;
                $topic['intro'] = $data->intro;
                $topic['full_intro'] = $data->full_intro;
                $topic['expert_id'] = $data->expert_id;
                $topic['status'] = $data->status;
                RedisCommon::setHash_Array($key, $topic);
            } else {
//                ApiException::Msgs(ApiException::TOPIC_NOT_EXIST);
                return false;
            }
        } else {
            $topic['topic_id'] = intval($topic['topic_id']);
        }
        return $topic;
    }

    static public function getLocation($location_id, $resident)
    {
        if ($resident) {
            return $resident;
        } elseif ($location_id) {
            return Yii::$app->db->createCommand("select name from location where id=$location_id")->queryScalar();
        } else {
            return '北京';
        }
    }

    public static function getLocations($expert_id)
    {
        $sql = "select l.name from expert_location el, location l 
                where el.expert_id=$expert_id and el.location_id=l.id
                order by el.sort";
        return Yii::$app->db->createCommand($sql)->queryColumn();
    }

    static public function getField($field_id)
    {
        return Yii::$app->db->createCommand("select name from field where id=$field_id")->queryScalar();
    }

    static public function getPoolList($id)
    {
        $show = Yii::$app->redis->get('position_show:' . $id);
        $r = Yii::$app->redis->zrevrange('position_top:' . $id, 0, -1);
        if (!$r) {
            $r = [];
        }
        $last = $show - count($r);
        if ($last > 0) {
            $list = Yii::$app->redis->zrevrange('position:' . $id, 0, -1);
            if ($list) {
                $lc = count($list);
                if ($last > $lc) {
                    $last = $lc;
                }
                $keys = array_rand($list, $last);
                $a = [];
                if ($last == 1) {
                    $a[] = $list[$keys];
                } else {
                    foreach ($keys as $k) {
                        $a[] = $list[$k];
                    }
                }
                $r = array_merge($r, $a);
            }
        }
        return $r;
    }

    static public function update($expert_id, $type, $info)
    {
        if (!$expert_id || !$type || $info === null) {
            ApiException::Msgs(ApiException::WRONG_PARAM);
        }
        //ExpertDB::model()->updateByPk($expert_id, array($type => $info));
        Yii::$app->db->createCommand()->update('expert', [$type => $info], 'expert_id = '.$expert_id)->execute();
        Yii::$app->redis->hset('expert:' . $expert_id, $type, $info);
    }

    public static function getScoreList($tags)
    {
        $max_score = 98;
        $min_score = 50;
        $score_adjust = 2;
        $score_rate = 0.7;

        $expert_score_sum = [];
        $expert_score_count = [];
        foreach ($tags as $tag) {
            $data = Yii::$app->redis->zrange('label_expert:' . $tag, 0, -1, 'WITHSCORES');
            foreach ($data as $expert_id => $score) {
                if (isset($expert_score_sum[$expert_id])) {
                    $expert_score_sum[$expert_id] += $score * $score_adjust;
                } else {
                    $expert_score_sum[$expert_id] = $score * $score_adjust;
                }
                if (isset($expert_score_count[$expert_id])) {
                    $expert_score_count[$expert_id]++;
                } else {
                    $expert_score_count[$expert_id] = 1;
                }
            }
        }

        //Yii::log('expert score count:' . json_encode($expert_score_count), 'warning');
        Yii::warning('expert score count:' . json_encode($expert_score_count));
        //Yii::log('expert score sum:' . json_encode($expert_score_sum), 'warning');
        Yii::warning('expert score sum:' . json_encode($expert_score_sum));

//        $base_score = $max_score - $min_score - max($expert_score_sum);
//        $result = [];
//        foreach ($expert_score_sum as $expert_id => $sum) {
//            $result[$expert_id] = $min_score + round($expert_score_count[$expert_id] / count($tags) * $base_score) + $sum;
//        }
//        return $result;

        $result = [];
        $ct = count($tags);
        $score_used = $max_score - $min_score;
        $s = $score_used * $score_rate;
        $c = $score_used * (1 - $score_rate);
        foreach ($expert_score_sum as $expert_id => $sum) {
            $result[$expert_id] = round($min_score + ($c * $expert_score_count[$expert_id] / $ct) + ($s * $sum / ($ct * 3 * $score_adjust)));
        }
        return $result;
    }

    public static function change($expert)
    {
        //getParam
        if ( version_compare(Yii::$app->request->post('ver'), '2.2') >= 0
            || Yii::$app->request->post('platform') != 2) {
            $expert['expert_id'] = $expert['show_id'];
        }
        unset($expert['show_id']);
        return $expert;
    }

    public static function backShowId($show_id)
    {
        if ($e = business\ExpertDB::findOne(['show_id' => $show_id])){
            return $e->expert_id;
        }
        return $show_id;
    }
}