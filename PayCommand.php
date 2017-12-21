<?php

namespace dakashuo\common;

use mycompany\business;
use Yii;
use yii\console\Controller;

/**
 * Created by PhpStorm.
 * User: caoxiang
 * Date: 16/9/6
 * Time: 下午6:09
 */
class PayCommand extends Controller
{
    public function actionCheckUnionResult()
    {
        $union = new UnionPay();
        $date = date('Y-m-d H:i:s', time() - 86400 * 3);
        $sql = "select pay_id from withdraw_log WHERE pay_type=3 and result=3 and ctime > '$date'";
        if ($data = Yii::$app->db->createCommand($sql)->queryColumn()) {
            foreach ($data as $id) {
                $r = $union->checkPayout($id);
//                echo $id.' '.$r."\n";
                if ($r == 1 || $r == 2) {
                    $cmd = Yii::$app->params['admin_path'] . '/yiic common withdrawAliCallBack --business_id=' . $id . ' --data=' . ($r == 1 ? 1 : 0);
                    Yii::warning('ali callback: ' . $cmd);
                    exec($cmd);
                }
                sleep(4);
            }
        }
    }
}