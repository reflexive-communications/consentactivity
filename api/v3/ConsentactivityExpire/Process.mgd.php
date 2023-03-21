<?php

return [
    [
        'name' => 'Cron:ConsentactivityExpire.Process',
        'entity' => 'Job',
        'params' => [
            'version' => 3,
            'name' => 'Call ConsentactivityExpire.Process API',
            'description' => 'Call ConsentactivityExpire.Process API for gathering the contacts with expired consents.',
            'run_frequency' => 'Daily',
            'api_entity' => 'ConsentactivityExpire',
            'api_action' => 'Process',
            'parameters' => '',
        ],
    ],
];
