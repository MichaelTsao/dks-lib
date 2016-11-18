<?php

return [
    'adminEmail' => 'wangjinlin@dakashuo.com',

    'testMoney' => false,
    'service_phone' => '4008918900',
    'fee_rate' => 0.1,
    'type_limit' => 8,
    'price_type' => 2,

    'weixin_pay_appid' => 'wx20e769a0e87b99e5',    // 1270184601@1270184601
    'weixin_pay_mchid' => '1270184601',
    'weixin_pay_key' => 'addd7e359620476c615a2551a624b339',
    'weixin_pay_secret' => 'bfe5c7499461d07d28b0ceacbf7da69d',

    'alipay_charset' => 'utf-8',  // utf-8 or gbk
    'alipay_cacert' => dirname(__FILE__) . '/../data/cacert.pem',
    'alipay_public_key' => dirname(__FILE__) . '/../data/ali_public_key.pem',
    'alipay_key' => 'cjdjvktt3a5v456chc8nuq2ldydbr9ex',
    'alipay_partner' => '2088021439358188',
    'alipay_seller_email' => "13811773096@163.com",

    'unionpay_mer_id' => '802110053110615',
    'unionpay_gateway' => 'https://gateway.95516.com/gateway/api/frontTransReq.do',
    'unionpay_app_gateway' => 'https://gateway.95516.com/gateway/api/appTransReq.do',
    'unionpay_server_gateway' => 'https://gateway.95516.com/gateway/api/backTransReq.do',
    'unionpay_cert' => dirname(__FILE__) . '/../data/acp_dis_sign.pfx',
    'unionpay_cert_cer' => dirname(__FILE__) . '/../data/certs/acp_prod_enc.cer',
    'unionpay_cert_password' => '116688',

    'meet_user_status' => [
        11 => '已预约，等待大咖回应',
        12 => '预约已取消',
        1 => '已预约，等待大咖回应',
        2 => '大咖已接受，等待我支付',
        3 => '预约已取消',
        4 => '预约已取消',
        5 => '已支付，与大咖联系',
        6 => '预约已取消',
        7 => '确认已完成，待致谢',
        8 => '预约已完成',
        9 => '预约已取消',
        10 => '已支付，待完成预约',
        13 => '预约已取消'
    ],
    'meet_expert_status' => [
        11 => '新预约，等待我回应',
        12 => '预约已取消',
        1 => '新预约，等待我回应',
        2 => '已接受预约，等待对方支付',
        3 => '预约已取消',
        4 => '预约已取消',
        5 => '对方已支付，联系TA',
        6 => '预约已取消',
        7 => '预约完成，等待对方致谢',
        8 => '预约完成，对方已致谢',
        9 => '预约已取消',
        10 => '对方已支付，待完成预约',
        13 => '预约已取消'
    ],

    'ask_user_status' => [
        11 => '等待大咖回答',
        12 => '已取消',
        1 => '等待大咖回答',
        3 => '已取消',
        4 => '已取消',
        7 => '大咖已回答',
        9 => '已取消',
        13 => '已取消'
    ],
    'ask_expert_status' => [
        11 => '新提问，等待我回答',
        12 => '已取消',
        1 => '新提问，等待我回答',
        3 => '已取消',
        4 => '已取消',
        7 => '已回答',
        9 => '已取消',
        13 => '已取消'
    ],

    'cancel_user_status' => [
        12 => '不符合“大咖说”预约规范',
        3 => '大咖已取消预约',
        4 => '大咖未确认',
        6 => '超时未付款',
        9 => '我主动取消',
        13 => '用户主动申请取消预约'
    ],
    'cancel_expert_status' => [
        12 => '预约取消',
        3 => '我主动拒绝',
        4 => '超时未确认',
        6 => '对方超时未付款',
        9 => '对方主动取消',
        13 => '用户主动申请取消预约'
    ],

    'ask_cancel_user_status' => [
        12 => '不符合“大咖说”提问规范',
        3 => '大咖已放弃回答',
        4 => '大咖未回答',
        13 => '用户主动申请取消提问'
    ],
    'ask_cancel_expert_status' => [
        12 => '预约取消',
        3 => '我主动拒绝',
        4 => '超时未回答',
        13 => '用户主动申请取消提问'
    ],

    'meet_user_remind' => [
        11 => '等待大咖回应。在大咖确认接受前，您可以取消预约。',
        1 => '等待大咖回应。在大咖确认接受前，您可以取消预约。',
        2 => '如需要发票，或有支付问题，请联系客服。',
        7 => '和大咖一起合个影，留个纪念吧。',
        8 => '这次约见受益匪浅？您可以再约这位大咖！',
        10 => "您可以和大咖聊聊，商议预约的方式（见面、电话等）和时间、地点。\n和大咖见面后，一起合个影，留个纪念吧。\n如无法完成预约，请及时联系客服。"
    ],
    'meet_expert_remind' => [
        1 => "接受预约后，请等待对方付款。\n对方付款后，可通过聊天功能，告知对方您方便的约见时间和地点等。",
        10 => "和对方聊聊，告知预约的方式（见面、电话等）和时间、地点。\n约见时和对方合个影留念吧！完成预约后请确认已完成。\n如无法完成预约，请及时联系客服。"
    ]

];
