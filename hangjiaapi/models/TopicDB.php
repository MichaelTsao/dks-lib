<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use \yii\db\ActiveRecord;
/**
 * This is the model class for table "topic".
 *
 * The followings are the available columns in table 'topic':
 * @property string $topic_id
 * @property integer $expert_id
 * @property string $name
 * @property string $intro
 * @property string $full_intro
 * @property integer $type
 * @property integer $status
 * @property string $ctime
 */
class TopicDB extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'topic';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('expert_id, type, status', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>200),
			array('intro, full_intro, ctime', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('topic_id, expert_id, name, intro, full_intro, type, ctime', 'safe', 'on'=>'search'),
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
			'topic_id' => 'Topic',
			'expert_id' => '行家',
			'name' => '话题',
			'intro' => '介绍',
			'full_intro' => '详情',
			'type' => '类型',
			'status' => '状态',
			'ctime' => '创建时间',
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

		$criteria->compare('topic_id',$this->topic_id,true);
		$criteria->compare('expert_id',$this->expert_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('intro',$this->intro,true);
		$criteria->compare('full_intro',$this->full_intro,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('ctime',$this->ctime,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return TopicDB the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
