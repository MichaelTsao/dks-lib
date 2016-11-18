<?php

Yii::setAlias('@tests', dirname(__DIR__) . '/tests/codeception');

$params = require(__DIR__ . '/params.php');
//$db = require(__DIR__ . '/db.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        //'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
//    $config['bootstrap'][] = 'gii';
//    $config['modules']['gii'] = [
//        'class' => 'yii\gii\Module',
//    ];
    $conf = require (__DIR__ . '/config_dev.php');
}
if (YII_ENV_TEST){
    $conf = require (__DIR__ . '/config_test.php');
}
if (YII_ENV_PROD){
    $conf = require (__DIR__ . '/config_prod.php');
}
return array_merge_recursive($config, $conf);
