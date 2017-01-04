<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use mycompany\common\Logic;
use mycompany\common\Result;
use mycompany\common\SortList;
use mycompany\common\WeiXin;
use yii\console\Controller;
use Yii;
use yii\data\Pagination;
use yii\redis\Connection;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class TestController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($code)
    {
        $wx = new WeiXin([
            'appId' => 'wxbd04c6f3a4768d5d',
            'appSecret' => 'f5214b4c4e803229d524b844b640cd26',
        ]);
        var_dump($wx->codeToSession($code));
    }
}
