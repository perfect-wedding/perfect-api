<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site specific Configuration
    |--------------------------------------------------------------------------
    |
    | These settings determine how the site is to be run or managed
    |
    */

    'site_name' => 'PerfectWedding.io',
    'site_slogan' => 'Building Experiences People Love',
    'currency_symbol' => '$',
    'currency' => 'USD',
    'use_queue' => true,
    'prefered_notification_channels' => [
        'mail',
    ],

    //['sms', 'mail'],

    'keep_successful_queue_logs' => true,
    'default_user_about' => 'Only Business Minded!',
    'strict_mode' => false,

    // Setting to true will prevent the Vcard Engine from generating Vcards with repeated content

    'rich_stats' => true,
    'slack_debug' => false,
    'slack_logger' => false,
    'force_https' => false,
    'verify_email' => false,
    'verify_phone' => false,
    'token_lifespan' => 1,
    'vcard_lifespan' => 10,

    //In Days

    'logo_type' => 'Filled',
    'auth_layout' => 'card',
    'frontend_link' => 'https://perfectwedding-spa.toneflix.cf',
    'payment_verify_url' => env('PAYMENT_VERIFY_URL', 'http://localhost:8080/payment/verify'),
    'default_banner' => 'http://127.0.0.1:8000/media/images/681513569_933221083.jpg',
    'auth_banner' => 'http://127.0.0.1:8000/media/images/1512735088_824110016.jpg',
    'welcome_title' => 'Hi {user_name}, Welcome to the market place again!',

    // {user_name} will be replaced with the user's name

    'welcome_info' => '{user_name} Do know that there is some randome information about the market place and Perfect weeding in general...',
    'welcome_banner' => 'http://127.0.0.1:8000/media/images/821077164_920229202.jpg',
    'paystack_public_key' => env('PAYSTACK_PUBLIC_KEY', 'pk_'),
    'identitypass_mode' => 'live',
    'identitypass_public_key' => env('IDENTITYPASS_PUBLIC_KEY'),
    'verification_strictness' => 'strict',

    // strict, normal, relaxed

    'trx_prefix' => 'TRX-',
    'company_verification_fee' => 5000,
    'task_completion_reward' => 1000,
    'min_withdraw_amount' => '1000',
    'auto_approve_withdraw' => false,
    'auto_payout_withdraw' => false,
    'contact_address' => '31 Gwari Avenue, Barnawa, Kaduna',
    'delete_notifications_after_days' => 10,
    'system' => [
        'identitypass' => [
            'live' => 'https://api.myidentitypass.com',
            'sandbox' => 'https://sandbox.myidentitypass.com',
            'app_id' => env('IDENTITYPASS_APP_ID'),
            'secret_key' => env('IDENTITYPASS_SECRET_KEY'),
            'sandbox_keys' => [
                'app_id' => env('IDENTITYPASS_SANDBOX_APP_ID', env('IDENTITYPASS_APP_ID')),
                'public_key' => env('IDENTITYPASS_SANDBOX_PUBLIC_KEY'),
                'secret_key' => env('IDENTITYPASS_SANDBOX_SECRET_KEY'),
            ],
        ],
        'paystack' => [
            'secret_key' => env('PAYSTACK_SECRET_KEY', 'sk_'),
        ],
        'ipinfo' => [
            'access_token' => env('IPINFO_ACCESS_TOKEN'),
        ],
    ],

    /*
        |---------------------------------------------------------------------------------
        | Message templates
        |---------------------------------------------------------------------------------
        | Variables include {username}, {name}, {firstname}, {lastname}, {site_name}, {message}, {reserved}
        |
    */

    'messages' => [
        'variables' => 'Available Variables: {username}, {name}, {firstname}, {lastname}, {site_name}, {message}, {reserved}. (Some variables may not apply to some actions)',
        'greeting' => 'Hello {username},',
        'mailing_list_sub' => 'You are receiving this email because you recently subscribed to our mailing list, what this means is that you will get every information about {site_name} before anyone else does. Thanks for your support!',
        'mailing_list_sub_admin' => '{name} has just joined the mailing list.',
        'mailing_list_exit' => 'We are sad to see you go, but we are okay with it, we hope to see you again.',
        'mailing_list_exit_admin' => '{name} has just left the mailing list.',
        'contact_message' => 'Thank you for contacting us, we will look in to your query and respond if we find the need to do so.',
        'contact_message_admin' => "{name} has just sent a message: \n {message}",
    ],
    'call_quality' => 360,
    'transaction_commission' => 6,
    'auto_call_booking' => true,
];
