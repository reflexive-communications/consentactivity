<?php

return [
    [
        'name' => 'Cron:Consentactivity.Tagging',
        'entity' => 'Job',
        'params' => [
            'version' => 3,
            'name' => 'Consentactivity: Tagging',
            'description' => 'Tag contacts with nearly expired consents',
            'run_frequency' => 'Daily',
            'api_entity' => 'Consentactivity',
            'api_action' => 'Tagging',
            'parameters' => '',
        ],
    ],
];
