<?php

use Civi\Api4\SavedSearch;
use Civi\Consentactivity\Config;
use Civi\RcBase\ApiWrapper\Save;
use CRM_Consentactivity_ExtensionUtil as E;

/**
 * Consentactivity.Tagging API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @throws API_Exception
 * @see civicrm_api3_create_success
 */
function civicrm_api3_consentactivity_Tagging($params): array
{
    $cfg = new Config(E::LONG_NAME);
    $cfg->load();
    $config = $cfg->get();
    // Don't need to execute the process if the search query is not set yet.
    if ($config['tagging-search-id'] === Config::DEFAULT_TAG_SEARCH_ID) {
        return civicrm_api3_create_success(['tagged' => 0, 'message' => E::ts('Saved Search is not set.')], $params, 'Consentactivity', 'tagging');
    }
    $search = SavedSearch::get(false)
        ->addWhere('id', '=', $config['tagging-search-id'])
        ->setLimit(1)
        ->execute()
        ->first();
    // calculate the saved search timestamp
    // - date('Y-m-d H:i') - interval expire + interval tagging
    // - it has to be used in the having condition
    $search['api_params']['having'][0][2] = date('Y-m-d H:i', strtotime(date('Y-m-d H:i').'- '.$config['consent-expiration-years'].' years + '.$config['consent-expiration-tagging-days'].' days'));
    // tag everybody in the search result set (use cursor method)
    $taggedContacts = 0;
    $contact_id = 0;
    $search['api_params']['limit'] = 1000;
    while (true) {
        $search['api_params']['where'] = [['id', '>', $contact_id]];
        $contacts = civicrm_api4('Contact', 'get', $search['api_params']);

        if (count($contacts) < 1) {
            break;
        }
        foreach ($contacts as $contact) {
            Save::tagContact($contact['id'], $config['tag-id']);
            $contact_id = $contact['id'];
            $taggedContacts++;
        }
    }

    return civicrm_api3_create_success(['found' => $taggedContacts, 'date' => $search['api_params']['having'][0][2]], $params, 'Consentactivity', 'tagging');
}
