<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\QueryBuilder;
use yii\data\ActiveDataProvider;//活动记录
/**
 * This is the model class for table "meet".
 *
 * The followings are the available columns in table 'meet':
 * @property string $meet_id
 * @property integer $uid
 * @property integer $expert_id
 * @property integer $topic_id
 * @property integer $meet_type
 * @property integer $period_length
 * @property integer $price
 * @property integer $price_type
 * @property integer $minutes
 * @property integer $user_price
 * @property double $fee_rate
 * @property string $question
 * @property string $intro
 * @property integer $status
 * @property integer $rate
 * @property string $chat_id
 * @property integer $chat_status
 * @property string $refuse_reason
 * @property string $comment
 * @property string $remarks
 * @property integer $comment_status
 * @property string $last_msg
 * @property string $ctime
 * @property string $check_time
 * @property string $confirm_time
 * @property string $cancel_time
 * @property string $pay_time
 * @property string $say_time
 * @property string $chat_time
 * @property string $meet_time
 * @property string $comment_time
 * @property string $last_msg_time
 * @property string $platform
 * @property string $show_id
 */
class MeetDB extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'meet';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('uid, expert_id, topic_id, price, minutes, user_price, status, rate, chat_status, comment_status,
			 meet_type, price_type, last_msg_role, period_length', 'numerical', 'integerOnly'=>true), // last_msg_role
			array('fee_rate', 'numerical'),
			array('chat_id', 'length', 'max'=>100),
			array('intro, question, refuse_reason, comment, remarks, last_msg, ctime, check_time, confirm_time, show_id, 
			cancel_time, pay_time, say_time, chat_time, meet_time, comment_time, last_msg_time, platform, confirm_role',
                'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('meet_id, uid, expert_id, topic_id, price, minutes, user_price, fee_rate, question, status, rate,
			chat_id, chat_status, refuse_reason, comment, remarks, comment_status, last_msg, ctime, check_time,
			confirm_time, cancel_time, pay_time, say_time, chat_time, meet_time, comment_time, last_msg_time',
                'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
//	public function relations() 2.0弃用 改用 hasMany
//	{
//		// NOTE: you may need to adjust the relation name and the related
//		// class name for the relations automatically generated below.
//		return array(
//		);
//	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'meet_id' => '订单编号',
			'uid' => '用户',
			'expert_id' => '行家',
			'topic_id' => '话题',
			'meet_type' => '订单类型',
			'period_length' => '周期长度',
			'price' => '单价',
			'minutes' => '时长（分钟）',
			'user_price' => '用户出价',
			'fee_rate' => '服务费率',
			'question' => '问题',
			'intro' => '个人介绍',
			'status' => '订单进度',
			'rate' => '评价',
			'chat_id' => '聊天ID',
			'chat_status' => '聊天状态',
			'refuse_reason' => '拒绝理由',
			'comment' => '评论',
			'remarks' => '备注',
			'comment_status' => '评论状态',
			'last_msg' => '最后聊天内容',
			'ctime' => '创建时间',
			'check_time' => '审核时间',
			'confirm_time' => '确认时间',
			'cancel_time' => '取消时间',
			'pay_time' => '支付时间',
			'say_time' => '聊天时间',
			'chat_time' => '联系时间',
			'meet_time' => '会见时间',
			'comment_time' => '评价时间',
			'last_msg_time' => '最后聊天时间',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=  \yii\db\ActiveRecord::find();

		$criteria->compare('meet_id',$this->meet_id,true);
		$criteria->compare('uid',$this->uid);
		$criteria->compare('expert_id',$this->expert_id);
		$criteria->compare('topic_id',$this->topic_id);
		$criteria->compare('price',$this->price);
		$criteria->compare('minutes',$this->minutes);
		$criteria->compare('user_price',$this->user_price);
		$criteria->compare('fee_rate',$this->fee_rate);
		$criteria->compare('question',$this->question,true);
		$criteria->compare('intro',$this->intro,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('rate',$this->rate);
		$criteria->compare('chat_id',$this->chat_id,true);
		$criteria->compare('chat_status',$this->chat_status);
		$criteria->compare('refuse_reason',$this->refuse_reason,true);
		$criteria->compare('comment',$this->comment,true);
		$criteria->compare('remarks',$this->remarks,true);
		$criteria->compare('comment_status',$this->comment_status);
		$criteria->compare('last_msg',$this->last_msg,true);
		$criteria->compare('ctime',$this->ctime,true);
		$criteria->compare('check_time',$this->check_time,true);
		$criteria->compare('confirm_time',$this->confirm_time,true);
		$criteria->compare('cancel_time',$this->cancel_time,true);
		$criteria->compare('pay_time',$this->pay_time,true);
		$criteria->compare('say_time',$this->say_time,true);
		$criteria->compare('chat_time',$this->chat_time,true);
		$criteria->compare('meet_time',$this->meet_time,true);
		$criteria->compare('comment_time',$this->comment_time,true);
		$criteria->compare('last_msg_time',$this->last_msg_time,true);

		return new ActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return MeetDB the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
