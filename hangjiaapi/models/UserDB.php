<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use \yii\db\ActiveRecord;
/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property string $uid
 * @property string $username
 * @property string $password
 * @property string $realname
 * @property string $nickname
 * @property string $phone
 * @property integer $gender
 * @property string $icon
 * @property string $title
 * @property string $company
 * @property string $intro
 * @property integer $status
 * @property string $ctime
 * @property string $change_passwd_time
 * @property string $login_time
 * @property string $platform
 */
class UserDB extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('gender, status', 'numerical', 'integerOnly'=>true),
			array('username, password, realname, nickname', 'length', 'max'=>50),
			array('phone', 'length', 'max'=>20),
			array('icon, title, company', 'length', 'max'=>200),
			array('intro, ctime, change_passwd_time, login_time, platform', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('uid, username, password, realname, nickname, phone, gender, icon, title, company, intro, status,
			ctime, change_passwd_time, login_time', 'safe', 'on'=>'search'),
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
			'uid' => 'Uid',
			'username' => '登录名',
			'password' => '密码',
			'realname' => '真实名',
			'nickname' => '昵称',
			'phone' => '手机号',
			'gender' => '性别',
			'icon' => '头像',
			'title' => '职位',
			'company' => '公司名',
			'intro' => '介绍',
			'status' => '状态',
			'ctime' => '创建时间',
			'change_passwd_time' => '最后更新密码时间',
			'login_time' => '最后登录时间',
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

		$criteria->compare('uid',$this->uid,true);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('realname',$this->realname,true);
		$criteria->compare('nickname',$this->nickname,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('gender',$this->gender);
		$criteria->compare('icon',$this->icon,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('company',$this->company,true);
		$criteria->compare('intro',$this->intro,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('ctime',$this->ctime,true);
		$criteria->compare('change_passwd_time',$this->change_passwd_time,true);
		$criteria->compare('login_time',$this->login_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return UserDB the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
