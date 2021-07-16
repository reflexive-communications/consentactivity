<?php
use CRM_Consentactivity_ExtensionUtil as E;
use Civi\Api4\EntityTag;
use Civi\Api4\SavedSearch;

/**
 * ConsentactivityTagging.Process API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_consentactivity_tagging_Process_spec(&$spec)
{
}

/**
 * ConsentactivityTagging.Process API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_consentactivity_tagging_Process($params)
{
    $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
    $cfg->load();
    $config = $cfg->get();
    // Don't need to execute the process if the search query is not set yet.
    if ($config['tagging-search-id'] === CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID) {
        return civicrm_api3_create_success(['tagged' => 0, 'message' => ts('Saved Search is not set.')], $params, 'ConsentactivityTagging', 'Process');
    }
    $search = SavedSearch::get(false)
        ->addWhere('id', '=', $config['tagging-search-id'])
        ->setLimit(1)
        ->execute()
        ->first();
    // calculate the saved search timestamp
    // - date('Y-m-d H:i') - interval expire + interval tagging
    // - it has to be used in the having condition
    $search['api_params']['having'][0][2] = date('Y-m-d H:i', strtotime(date('Y-m-d H:i') . '- '.$config['consent-expiration-years'].' years + '.$config['consent-expiration-tagging-days'].' days'));
    // tag everybody in the search result set.
    $taggedContacts = 0;
    // To prevent the API timeout issues caused by big resultset,
    // the limit and offset is managed during the requests.
    $search['limit'] = 25;
    $search['offset'] = 0;
    while ($search['limit'] > 0) {
        // Traditional api call solution to be able to pass the api_params.
        $contacts = civicrm_api4('Contact', 'get', $search['api_params']);
        foreach ($contacts as $contact) {
            $results = EntityTag::create(false)
                ->addValue('entity_table', 'civicrm_contact')
                ->addValue('entity_id', $contact['id'])
                ->addValue('tag_id', $config['tag-id'])
                ->execute();
        }
        $taggedContacts = $taggedContacts + count($contacts);
        $search['limit'] = count($contacts);
        $search['offset'] = $search['limit'] + $search['offset'];
    }
    // Spec: civicrm_api3_create_success($values = 1, $params = [], $entity = NULL, $action = NULL)
    return civicrm_api3_create_success(['tagged' => $taggedContacts, 'date' => $search['api_params']['having'][0][2]], $params, 'ConsentactivityTagging', 'Process');
}
