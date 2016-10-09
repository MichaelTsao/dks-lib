<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "meet_ask".
 *
 * The followings are the available columns in table 'meet_ask':
 * @property string $id
 * @property string $meet_id
 * @property string $answer
 * @property integer $length
 * @property integer $sort
 */
class MeetAsk extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'meet_ask';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sort, length', 'numerical', 'integerOnly'=>true),
			array('meet_id', 'length', 'max'=>11),
			array('answer', 'length', 'max'=>200),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, meet_id, answer, sort', 'safe', 'on'=>'search'),
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
			'id' => 'ID',
			'meet_id' => '订单ID',
			'answer' => '问答答案',
			'length' => '答案长度',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('meet_id',$this->meet_id,true);
		$criteria->compare('answer',$this->answer,true);
		$criteria->compare('sort',$this->sort);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return MeetAsk the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public static function getList($meet_id)
	{
		$r = [];
		$data = self::model()->findAllByAttributes(['meet_id' => $meet_id], ['order' => 'sort']);
		foreach ($data as $item) {
			$r[] = [
				'file_id' => $item->id,
				'answer' => Yii::app()->params['web_host'] . '/ask/play/' . $item->id,
				'length' => $item->length,
				'listened' => $item->listened,
			];
		}
        return $r;
	}

    public static function setList($type, $id, $ask_id, $level)
    {
        Yii::app()->redis->getClient()->zAdd("ask_$type:" . $id, time() * $level, $ask_id);
    }
}
