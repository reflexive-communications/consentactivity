<?php

use Civi\RcBase\ApiWrapper\Remove;
use Civi\RcBase\ApiWrapper\Save;
use CRM_Consentactivity_ExtensionUtil as E;
use Civi\Api4\SavedSearch;

/**
 * ConsentactivityExpire.Process API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_consentactivity_expire_Process_spec(&$spec)
{
}

/**
 * ConsentactivityExpire.Process API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @throws API_Exception
 * @see civicrm_api3_create_success
 */
function civicrm_api3_consentactivity_expire_Process($params)
{
    $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
    $cfg->load();
    $config = $cfg->get();
    // Don't need to execute the process if the search query is not set yet.
    if ($config['saved-search-id'] === CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID) {
        return civicrm_api3_create_success(['handled' => 0, 'message' => E::ts('Saved Search is not set.')], $params, 'ConsentactivityExpire', 'Process');
    }
    $search = SavedSearch::get(false)
        ->addWhere('id', '=', $config['saved-search-id'])
        ->setLimit(1)
        ->execute()
        ->first();
    // calculate the saved search timestamp
    // - date('Y-m-d H:i') - interval expire
    // - it has to be used in the having condition
    $search['api_params']['having'][0][2] = date('Y-m-d H:i', strtotime(date('Y-m-d H:i').'- '.$config['consent-expiration-years'].' years'));
    $handledContacts = 0;
    $errors = [];
    $contact_id = 0;
    $search['api_params']['limit'] = 1000;
    while (true) {
        $search['api_params']['where'] = [['id', '>', $contact_id]];
        $contacts = civicrm_api4('Contact', 'get', $search['api_params']);

        if (count($contacts) < 1) {
            break;
        }
        foreach ($contacts as $contact) {
            try {
                CRM_Consentactivity_Service::anonymizeContact($contact['id']);
                Save::tagContact($contact['id'], $config['expired-tag-id']);
                Remove::tagFromContact($contact['id'], $config['tag-id']);
                $handledContacts++;
            } catch (Exception $e) {
                $errors[] = 'Anonymize contact failed. Id: '.$contact['id'].' Details: '.$e->getMessage();
            }
            $contact_id = $contact['id'];
        }
    }
    $response = [
        'handled' => $handledContacts,
        'date' => $search['api_params']['having'][0][2],
        'errors' => $errors,
    ];
    if (count($errors)) {
        return civicrm_api3_create_error('Errors occurred during the execution.', $response);
    }

    return civicrm_api3_create_success($response, $params, 'ConsentactivityExpire', 'Process');
}
