<?php

return [
    [
        'name' => 'Cron:Consentactivity.Expire',
        'entity' => 'Job',
        'params' => [
            'version' => 3,
            'name' => 'Consentactivity: Anonymize',
            'description' => 'Anonymize contacts with expired consents',
            'run_frequency' => 'Daily',
            'api_entity' => 'Consentactivity',
            'api_action' => 'Expire',
            'parameters' => '',
        ],
    ],
];
