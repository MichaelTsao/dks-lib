<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use \yii\db\ActiveRecord;
/**
 * This is the model class for table "user_fake".
 *
 * The followings are the available columns in table 'user_fake':
 * @property string $fake_id
 * @property string $username
 * @property string $icon
 * @property string $expert_name
 * @property string $expert_topic
 * @property string $comment
 * @property string $rate
 * @property string $comment_time
 */
class UserFake extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'user_fake';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('username, expert_name, comment', 'required'),
			array('username, expert_name', 'length', 'max'=>50),
			array('icon, expert_topic, comment', 'length', 'max'=>200),
			array('rate', 'length', 'max'=>11),
			array('comment_time', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('fake_id, username, icon, expert_name, expert_topic, comment, rate, comment_time', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'fake_id' => 'ID',
			'username' => '用户名',
			'icon' => '用户头像',
			'expert_name' => '行家名字',
			'expert_topic' => '话题',
			'comment' => '评论',
			'rate' => '评分',
			'comment_time' => '评论时间',
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

		$criteria=new CDbCriteria;

		$criteria->compare('fake_id',$this->fake_id,true);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('icon',$this->icon,true);
		$criteria->compare('expert_name',$this->expert_name,true);
		$criteria->compare('expert_topic',$this->expert_topic,true);
		$criteria->compare('comment',$this->comment,true);
		$criteria->compare('rate',$this->rate,true);
		$criteria->compare('comment_time',$this->comment_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return UserFake the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
