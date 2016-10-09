<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use \yii\db\ActiveRecord;
/**
 * This is the model class for table "pay_log".
 *
 * The followings are the available columns in table 'pay_log':
 * @property string $id
 * @property string $order_id
 * @property integer $pay_type
 * @property string $vender_id
 * @property integer $meet_id
 * @property integer $uid
 * @property string $price
 * @property integer $status
 * @property string $verder_str
 * @property string $chat_id
 * @property string $ctime
 * @property string $pay_time
 */
class PayLog extends ActiveRecord
{
    const TYPE_ALIPAY = 1;
    const TYPE_WEIXIN = 2;
    const TYPE_UNION = 3;
    const TYPE_OFFLINE = 4;
    public static $types = [
        self::TYPE_ALIPAY => '支付宝',
        self::TYPE_WEIXIN => '微信支付',
        self::TYPE_UNION => '银联支付',
        self::TYPE_OFFLINE => '线下支付',
    ];
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'pay_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('pay_type, meet_id, uid, status', 'numerical', 'integerOnly'=>true),
			array('order_id', 'length', 'max'=>50),
			array('vender_id', 'length', 'max'=>200),
			array('price', 'length', 'max'=>9),
			array('verder_str, ctime, pay_time, chat_id', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, order_id, pay_type, vender_id, meet_id, uid, price, status, verder_str, ctime, pay_time', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
//	public function relations()
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
			'id' => 'ID',
			'order_id' => '订单ID',
			'pay_type' => '支付方式',
			'vender_id' => '支付方订单ID',
			'meet_id' => '约见',
			'uid' => '用户',
			'price' => '费用',
			'status' => '状态',
			'verder_str' => '支付方返回信息',
			'ctime' => '创建时间',
			'pay_time' => '支付时间',
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
		$criteria->compare('order_id',$this->order_id,true);
		$criteria->compare('pay_type',$this->pay_type);
		$criteria->compare('vender_id',$this->vender_id,true);
		$criteria->compare('meet_id',$this->meet_id);
		$criteria->compare('uid',$this->uid);
		$criteria->compare('price',$this->price,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('verder_str',$this->verder_str,true);
		$criteria->compare('ctime',$this->ctime,true);
		$criteria->compare('pay_time',$this->pay_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return PayLog the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
