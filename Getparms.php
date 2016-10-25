<?php
/**
 * Created by PhpStorm.
 * User: jinlin wang
 * Date: 2016/10/10
 * Time: 17:31
 */

namespace mycompany\common;

use Yii;

class Getparms
{
    public static function Getparm($name = null, $defaultValue = null){
        $request = Yii::$app->request;
        if ($request->isGet){
            return $request->get($name, $defaultValue);
        }else{
            return $request->post($name, $defaultValue);
        }
    }
}