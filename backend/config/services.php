<?php
return [
    // First payment integration: WeChat Pay. Implement adapter only after merchant credentials exist.
    'payment' => [
        'driver' => env('PAYMENT_DRIVER', 'null'), // null|wechat_pay
        'wechat_pay' => [
            'merchant_id' => env('WECHAT_PAY_MERCHANT_ID', ''),
            'merchant_serial' => env('WECHAT_PAY_MERCHANT_SERIAL', ''),
            'api_v3_key' => env('WECHAT_PAY_API_V3_KEY', ''),
            'private_key_path' => env('WECHAT_PAY_PRIVATE_KEY_PATH', ''),
            'platform_certificate_path' => env('WECHAT_PAY_PLATFORM_CERT_PATH', ''),
        ],
        'alipay' => [
            'app_id' => env('ALIPAY_APP_ID', ''),
            'app_private_key_path' => env('ALIPAY_APP_PRIVATE_KEY_PATH', ''),
            'public_key_path' => env('ALIPAY_PUBLIC_KEY_PATH', ''),
            'gateway' => env('ALIPAY_GATEWAY', ''),
        ],
        'douyin_pay' => [
            'app_id' => env('DOUYIN_PAY_APP_ID', ''),
            'merchant_id' => env('DOUYIN_PAY_MCH_ID', ''),
            'secret' => env('DOUYIN_PAY_SECRET', ''),
            'gateway' => env('DOUYIN_PAY_GATEWAY', ''),
        ],
        'jd_pay' => [
            'merchant_no' => env('JD_PAY_MERCHANT_NO', ''),
            'des_key' => env('JD_PAY_DES_KEY', ''),
            'rsa_private_key_path' => env('JD_PAY_RSA_PRIVATE_KEY_PATH', ''),
            'rsa_public_key_path' => env('JD_PAY_RSA_PUBLIC_KEY_PATH', ''),
            'gateway' => env('JD_PAY_GATEWAY', ''),
        ],
    ],
    // S3-compatible: MinIO local, then OSS/COS/S3 production endpoint.
    'storage' => [
        'driver' => env('STORAGE_DRIVER', 'local'), // local|s3
        's3' => [
            'endpoint' => env('S3_ENDPOINT', ''),
            'region' => env('S3_REGION', 'ap-shanghai'),
            'bucket' => env('S3_BUCKET', ''),
            'key' => env('S3_ACCESS_KEY', ''),
            'secret' => env('S3_SECRET_KEY', ''),
        ],
    ],
    // Provider adapters: Kuaidi100/Shippo etc., selected only after business choice.
    'logistics' => ['driver' => env('LOGISTICS_DRIVER', 'null')],
    // SMS (Aliyun/Tencent) and SMTP use separate adapters/credentials.
    'notification' => ['sms_driver' => env('SMS_DRIVER', 'null'), 'mail_dsn' => env('MAILER_DSN', '')],
];
