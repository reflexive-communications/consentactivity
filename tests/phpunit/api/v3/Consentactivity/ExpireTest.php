<?php

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\EntityTag;
use Civi\Consentactivity\HeadlessTestCase;
use CRM_Consentactivity_ExtensionUtil as E;

/**
 * @group headless
 */
class api_v3_Consentactivity_ExpireTest extends HeadlessTestCase
{
    /**
     * @return void
     * @throws \CiviCRM_API3_Exception
     */
    public function testApiProcessDefaultSearchId()
    {
        $result = civicrm_api3('Consentactivity', 'expire');
        self::assertSame(0, $result['values']['handled']);
        self::assertSame(E::ts('Saved Search is not set.'), $result['values']['message']);
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
        $config['tag-id'] = '1';
        $config['expired-tag-id'] = '1';
        $config['saved-search-id'] = CRM_Consentactivity_Service::savedSearchExpired($activityType['name'], $config['tag-id'], $config['expired-tag-id'], false)['id'];
        $cfg->update($config);
        $result = civicrm_api3('Consentactivity', 'expire');
        self::assertSame(0, $result['values']['handled']);
        self::assertArrayHasKey('date', $result['values']);
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
        // create contact, tag and activity
        $contact = Contact::create(false)
            ->addValue('first_name', 'test')
            ->addValue('last_name', 'test')
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $activityDate = date('Y-m-d H:i', strtotime(date('Y-m-d H:i').'- '.$config['consent-expiration-years'].' years - 10 days'));
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
        $config['expired-tag-id'] = '2';
        $config['saved-search-id'] = CRM_Consentactivity_Service::savedSearchExpired($activityType['name'], $config['tag-id'], $config['expired-tag-id'], false)['id'];
        $cfg->update($config);
        EntityTag::create(false)
            ->addValue('entity_table', 'civicrm_contact')
            ->addValue('entity_id', $contact['id'])
            ->addValue('tag_id', $config['tag-id'])
            ->execute();
        $result = civicrm_api3('Consentactivity', 'expire');
        self::assertCount(0, $result['values']['errors']);
        self::assertSame(1, $result['values']['handled'], 'Invalid number of handled: '.$result['values']['handled']);
        $updatedContact = Contact::get(false)
            ->addWhere('id', '=', $contact['id'])
            ->setLimit(1)
            ->addSelect(
                'do_not_email',
                'do_not_phone',
                'do_not_mail',
                'do_not_sms',
                'do_not_trade',
                'is_opt_out',
                'first_name',
                'last_name',
                'middle_name',
                'display_name',
                'email_greeting_display',
                'postal_greeting_display',
                'addressee_display',
                'nick_name',
                'sort_name',
                'external_identifier',
                'image_url',
                'api_key',
                'birth_date',
                'deceased_date',
                'employer_id',
                'job_title',
                'gender_id'
            )
            ->execute()
            ->first();
        $contactFieldsThatNeedsToBeDeleted = [
            'first_name',
            'last_name',
            'middle_name',
            'display_name',
            'nick_name',
            'sort_name',
            'external_identifier',
            'api_key',
            'birth_date',
            'deceased_date',
            'employer_id',
            'job_title',
            'gender_id',
        ];
        foreach ($contactFieldsThatNeedsToBeDeleted as $field) {
            self::assertEmpty($updatedContact[$field], 'The '.$field.' field should be empty, but it is '.$updatedContact[$field]);
        }
        $privacyFieldsThatNeedsToBeSet = [
            'do_not_email',
            'do_not_phone',
            'do_not_mail',
            'do_not_sms',
            'do_not_trade',
            'is_opt_out',
        ];
        foreach ($privacyFieldsThatNeedsToBeSet as $field) {
            self::assertTrue($updatedContact[$field], 'The consent field '.$field.' should be set.');
        }
        foreach (CRM_Consentactivity_Service::CONTACT_DATA_ENTITIES as $entity) {
            $numberOfEntities = $entity::get(false)
                ->addWhere('contact_id', '=', $contact['id'])
                ->selectRowCount()
                ->execute();
            self::assertCount(0, $numberOfEntities, 'Invalid number for the '.$entity.' entity.');
        }
        // check tag on the contact
        $entityTags = EntityTag::get(false)
            ->addWhere('entity_table', '=', 'civicrm_contact')
            ->addWhere('entity_id', '=', $contact['id'])
            ->addWhere('tag_id', '=', $config['tag-id'])
            ->execute();
        self::assertCount(0, $entityTags, 'Nearly expired tag not removed');
        $entityTags = EntityTag::get(false)
            ->addWhere('entity_table', '=', 'civicrm_contact')
            ->addWhere('entity_id', '=', $contact['id'])
            ->addWhere('tag_id', '=', $config['expired-tag-id'])
            ->execute();
        self::assertCount(1, $entityTags, 'Anonymized tag not added');
    }
}
