<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use mycompany\common\SortList;
use yii\console\Controller;
use Yii;
use yii\redis\Connection;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex()
    {
        $l = new SortList(['key' => 'b']);
//        $l->create(['b' => 10, 'c' => 3]);
        $a = $l->all();
        var_dump($a);
        var_dump($l->count());
    }
}
