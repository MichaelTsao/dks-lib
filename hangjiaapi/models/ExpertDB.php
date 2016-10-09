<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "expert".
 *
 * The followings are the available columns in table 'expert':
 * @property string $expert_id
 * @property integer $uid
 * @property string $name
 * @property string $title
 * @property string $brief
 * @property string $cover
 * @property integer $location
 * @property integer $work_years
 * @property integer $field
 * @property integer $price
 * @property integer $period_price
 * @property double $meet_hour
 * @property double $period_length
 * @property integer $rate
 * @property double $hours
 * @property string $intro
 * @property string $full_intro
 * @property integer $status
 * @property integer $access_status
 * @property integer $period_status
 * @property string $ctime
 * @property integer $sort
 * @property string $show_id
 */
class ExpertDB extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'expert';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('uid, location, work_years, field, price, period_price, rate, status, access_status, period_status, sort', 'numerical', 'integerOnly'=>true),
			array('meet_hour, hours, period_length', 'numerical'),
			array('name', 'length', 'max'=>50),
			array('title', 'length', 'max'=>100),
			array('brief, cover', 'length', 'max'=>200),
			array('intro, full_intro, ctime, show_id', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('expert_id, uid, name, title, brief, cover, location, work_years, field, price, meet_hour, rate, hours, intro, full_intro, status, access_status, ctime, sort', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
//	public function relations() 2.0弃用，要声明一个关联关系，只需简单地定义一个 getter
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
			'expert_id' => 'Expert',
			'uid' => '用户',
			'name' => '名字',
			'title' => '职位',
			'brief' => '简述',
			'cover' => '封面图',
			'location' => '地域',
			'work_years' => '工作年限',
			'field' => '领域',
			'price' => '单价',
			'meet_hour' => '可约时长',
			'rate' => '评价',
			'hours' => '约见时长',
			'intro' => '简介',
			'full_intro' => '介绍',
			'status' => '状态',
			'access_status' => '是否接受预约',
			'ctime' => '创建时间',
			'sort' => '排序',
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

		$criteria->compare('expert_id',$this->expert_id,true);
		$criteria->compare('uid',$this->uid);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('brief',$this->brief,true);
		$criteria->compare('cover',$this->cover,true);
		$criteria->compare('location',$this->location);
		$criteria->compare('work_years',$this->work_years);
		$criteria->compare('field',$this->field);
		$criteria->compare('price',$this->price);
		$criteria->compare('meet_hour',$this->meet_hour);
		$criteria->compare('rate',$this->rate);
		$criteria->compare('hours',$this->hours);
		$criteria->compare('intro',$this->intro,true);
		$criteria->compare('full_intro',$this->full_intro,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('access_status',$this->access_status);
		$criteria->compare('ctime',$this->ctime,true);
		$criteria->compare('sort',$this->sort);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ExpertDB the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
