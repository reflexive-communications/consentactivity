<?php

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\EntityTag;
use Civi\Consentactivity\HeadlessTestCase;
use CRM_Consentactivity_ExtensionUtil as E;

/**
 * @group headless
 */
class api_v3_ConsentactivityTagging_ProcessTest extends HeadlessTestCase
{
    /**
     * @return void
     * @throws \CiviCRM_API3_Exception
     */
    public function testApiProcessDefaultSearchId()
    {
        $result = civicrm_api3('ConsentactivityTagging', 'process');
        self::assertSame(0, $result['values']['tagged']);
        self::assertSame(ts('Saved Search is not set.'), $result['values']['message']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \CiviCRM_API3_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testApiProcessNoRelevantContact()
    {
        // create a contact
        Contact::create(false)
            ->addValue('first_name', 'test')
            ->addValue('last_name', 'test')
            ->addValue('contact_type', 'Individual')
            ->execute();
        // setup tag and search
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $activityType = CRM_Consentactivity_Service::getActivityType($config['option-value-id']);
        $config['expired-tag-id'] = '1';
        $config['tagging-search-id'] = CRM_Consentactivity_Service::savedSearchTagging($activityType['name'], $config['expired-tag-id'], false)['id'];
        $cfg->update($config);
        $result = civicrm_api3('ConsentactivityTagging', 'process', []);
        self::assertSame(0, $result['values']['found']);
        self::assertTrue(array_key_exists('date', $result['values']));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \CiviCRM_API3_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testApiProcessWithContact()
    {
        // create contact and activity
        $contact = Contact::create(false)
            ->addValue('first_name', 'test')
            ->addValue('last_name', 'test')
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $activityDate = date('Y-m-d H:i', strtotime(date('Y-m-d H:i').'- '.$config['consent-expiration-years'].' years'));
        Activity::create(false)
            ->addValue('activity_type_id', $config['activity-type-id'])
            ->addValue('source_contact_id', $contact['id'])
            ->addValue('target_contact_id', $contact['id'])
            ->addValue('status_id:name', 'Completed')
            ->addValue('activity_date_time', $activityDate)
            ->execute();
        // setup tag and search
        $activityType = CRM_Consentactivity_Service::getActivityType($config['option-value-id']);
        $config['tag-id'] = '1';
        $config['expired-tag-id'] = '1';
        $config['tagging-search-id'] = CRM_Consentactivity_Service::savedSearchTagging($activityType['name'], $config['expired-tag-id'], false)['id'];
        $cfg->update($config);
        $result = civicrm_api3('ConsentactivityTagging', 'process');
        self::assertSame(1, $result['values']['found']);
        self::assertTrue(array_key_exists('date', $result['values']));
        // check tag on the contact
        $entityTags = EntityTag::get()
            ->addWhere('entity_table', '=', 'civicrm_contact')
            ->addWhere('entity_id', '=', $contact['id'])
            ->addWhere('tag_id', '=', $config['tag-id'])
            ->execute();
        self::assertSame(1, count($entityTags));
    }
}
