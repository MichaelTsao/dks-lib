<?php
namespace mycompany\hangjiaapi\models;

use Yii;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "device_user".
 *
 * The followings are the available columns in table 'device_user':
 * @property string $device_id
 * @property integer $uid
 * @property integer $type
 * @property string $ctime
 */
class DeviceUser extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return 'device_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('uid, type', 'numerical', 'integerOnly'=>true),
			array('device_id', 'length', 'max'=>200),
			array('ctime', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('device_id, uid, type, ctime', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'device_id' => '设备',
			'uid' => '用户',
			'type' => '设备类型',
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

		$criteria->compare('device_id',$this->device_id,true);
		$criteria->compare('uid',$this->uid);
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
	 * @return DeviceUser the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	static public function create($uid, $device_id, $platform){
        if(!$device_id || !$platform || !$uid) {
            return false;
        }
        $du = DeviceUser::model()->findByAttributes(array('device_id'=>$device_id));
        if (!$du) {
            $du = new DeviceUser();
            $du->device_id = $device_id;
            $du->type = $platform;
        }
        $du->uid = $uid;
        $du->save();
        return true;
    }
}
