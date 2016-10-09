<?php
namespace mycompany\hangjiaapi\models;


/**
 * This is the model class for table "account".
 *
 * The followings are the available columns in table 'account':
 * @property string $id
 * @property string $uid
 * @property string $bank
 * @property string $name
 * @property string $account
 * @property integer $type
 * @property integer $used
 */
class Account extends MyModel
{
    const TYPE_WEIXIN_H5 = 1;
    const TYPE_WEIXIN_APP = 2;
    const TYPE_ALIPAY = 3;
    const TYPE_UNION = 4;
    public static $types = [
        self::TYPE_WEIXIN_H5 => '微信-H5',
        self::TYPE_WEIXIN_APP => '微信-app',
        self::TYPE_ALIPAY => '支付宝',
        self::TYPE_UNION => '银联',
    ];

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'account';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, used', 'numerical', 'integerOnly'=>true),
			array('uid', 'length', 'max'=>11),
			array('name', 'length', 'max'=>50),
			array('account, bank', 'length', 'max'=>500),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, uid, name, account, type, used', 'safe', 'on'=>'search'),
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
			'uid' => '用户ID',
			'name' => '姓名',
			'account' => '账号',
			'type' => '类型（1->微信，2->支付宝，3->银行账号）',
			'used' => '是否使用（0->未使用，1->已使用）',
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
		$criteria->compare('uid',$this->uid,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('account',$this->account,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('used',$this->used);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Account the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    public static function setDefault($uid, $type)
    {
        $a = Account::model()->findAllByAttributes(['uid' => $uid]);
        foreach ($a as $item) {
            if ($item->type == $type) {
                $item->used = 1;
            }else{
                $item->used = 0;
            }
            $item->save();
        }
	}
}
