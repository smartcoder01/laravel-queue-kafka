<?php

return [
    'driver' => 'kafka',
    'queue' => env('KAFKA_QUEUE', 'default'),
    'broker_list' => env('KAFKA_BROKER_LIST', 'kafka:29092'),
    'group_id' => env('KAFKA_GROUP_ID', 'laravel-consumer-group'),
    'default_configuration' => [
        'enable.auto.commit' => env('KAFKA_ENABLE_AUTO_COMMIT', 'true'),
    ]
];
