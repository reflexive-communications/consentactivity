<?php

return [
    [
        'name' => 'Cron:ConsentactivityTagging.Process',
        'entity' => 'Job',
        'params' => [
            'version' => 3,
            'name' => 'Call ConsentactivityTagging.Process API',
            'description' => 'Call ConsentactivityTagging.Process API for gathering the contacts with nearly expired consents.',
            'run_frequency' => 'Daily',
            'api_entity' => 'ConsentactivityTagging',
            'api_action' => 'Process',
            'parameters' => '',
        ],
    ],
];
