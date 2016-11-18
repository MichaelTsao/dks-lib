<?php

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=expert',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 6,
        ],
        'redis8' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 8,
            'prefix' => 'hj.',
            'verify' => ['HGETALL']
        ],
        'sphinx' => [
            'class' => 'yii\sphinx\Connection',
            'dsn' => 'mysql:host=127.0.0.1;port=9306;',
            'username' => '',
            'password' => '',
        ]
    ],
    'params'=>[
        'env' => 'dev',
        'img_host'=>'http://api.hangjiashuo.com.cn',
        'api_host'=>'http://api.hangjiashuo.com.cn',
        'web_host'=>'http://new.h5.hangjiashuo.com.cn',
        'ffmpeg_cmd' => '/usr/local/bin/ffmpeg',
        'admin_path' => '/data/www/hangjia_adm/protected',

        'wx_id'=>'wx37c81fe0f40f5093',  // acct@hangjiashuo.com
        'wx_key'=>'5fd8c4141656928dfa004f85348b4e4a',
        'wx_pay_key'=>'eba72870e78cd79194d507530c839d59',
        'wx_mch_id' => '1265408201',

        'weixin_push_template' => 'Y5CnMTODNRkr3LC10sg-tDrLD9gqXMeQqMqqseu-tuI',
        'home_id'=>1,
        'search_id'=>2,
        'recommend_id'=>3,
        'hot_topic_id'=>4,
        'sphinx_host'=>'localhost',
        'kefu_phone'=>['13651280055', '13910399060'],

        'lean_cloud_id'=>'AwBbAXJ1qJSFq8fxRwlPYr1e',
        'lean_cloud_key'=>'rXKCMQEHOW4Sh72Pt8sFAepS',
        'lean_cloud_master'=>'8opY3lg3xrHJu9SfLO3aeiJS',
        'lean_cloud_push_type' => 'dev'
    ]
];
