<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
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
