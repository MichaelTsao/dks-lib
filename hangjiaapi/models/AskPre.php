<?php
namespace mycompany\hangjiaapi\models;

use yii\db\ActiveQuery;
/**
 * This is the model class for table "ask".
 *
 * The followings are the available columns in table 'ask':
 * @property string $ask_id
 * @property integer $uid
 * @property integer $expert_id
 * @property string $question
 * @property integer $price
 * @property integer $status
 * @property integer $platform
 * @property string $ctime
 */
class AskPre extends MyActiveRecord
{
	const STATUS_NEW = 1;
	const STATUS_DONE = 2;

	/**
	 * @return string the associated database table name
	 */
    public static function tableName()
	{
		return 'ask_pre';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('uid, expert_id, price, status, platform', 'numerical', 'integerOnly'=>true),
			array('question', 'length', 'max'=>1000),
			array('ctime', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('ask_id, uid, expert_id, question, ctime, price', 'safe', 'on'=>'search'),
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
			'ask_id' => 'ID',
			'uid' => '用户',
			'expert_id' => '大咖',
			'question' => '问题',
			'price' => '价格',
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
        $customers = Customer::find();
		//$criteria=new CDbCriteria;

        $customers->compare('ask_id',$this->ask_id,true);
        $customers->compare('uid',$this->uid);
        $customers->compare('expert_id',$this->expert_id);
        $customers->compare('question',$this->question,true);
        $customers->compare('price',$this->price,true);
        $customers->compare('ctime',$this->ctime,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$customers,
		));
	}

	/**
     * yii 2.0  不可用
     *
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return AskPre the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
