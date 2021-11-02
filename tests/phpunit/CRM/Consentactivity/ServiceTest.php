<?php

use CRM_Consentactivity_ExtensionUtil as E;
use Civi\Api4\Activity;
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Email;
use Civi\Api4\EntityTag;
use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use Civi\Api4\IM;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Phone;
use Civi\Api4\Website;

/**
 * Service class test cases.
 *
 * @group headless
 */
class CRM_Consentactivity_ServiceTest extends CRM_Consentactivity_HeadlessBase
{
    public function testPostProcessMissingParameter()
    {
        $form = new CRM_Profile_Form_Edit();
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $form->setVar('_id', $contact['id']);
        $submit = [
        ];
        $form->setVar('_submitValues', $submit);
        self::assertEmpty(CRM_Consentactivity_Service::postProcess(CRM_Profile_Form_Edit::class, $form), 'PostProcess supposed to be empty.');
        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));
    }
    public function testPostProcessInvalidContactId()
    {
        $form = new CRM_Profile_Form_Edit();
        $form->setVar('_id', 0);
        $submit = [
            'is_opt_out' => ''
        ];
        $form->setVar('_submitValues', $submit);
        self::expectException(CRM_Core_Exception::class);
        self::assertEmpty(CRM_Consentactivity_Service::postProcess(CRM_Profile_Form_Edit::class, $form), 'PostProcess supposed to be empty.');
    }
    public function testPostProcess()
    {
        $form = new CRM_Profile_Form_Edit();
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $form->setVar('_id', $contact['id']);
        $submit = [
            'is_opt_out' => ''
        ];
        $form->setVar('_submitValues', $submit);
        self::assertEmpty(CRM_Consentactivity_Service::postProcess(CRM_Profile_Form_Edit::class, $form), 'PostProcess supposed to be empty.');
        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));
    }
    public function testPostProcessWithUpdatedTagId()
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $cfg['tag-id'] = 1;
        $config->update($cfg);
        $form = new CRM_Profile_Form_Edit();
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        // tag the contact
        EntityTag::create(false)
            ->addValue('entity_table', 'civicrm_contact')
            ->addValue('entity_id', $contact['id'])
            ->addValue('tag_id', $cfg['tag-id'])
            ->execute();
        $form->setVar('_id', $contact['id']);
        $submit = [
            'is_opt_out' => ''
        ];
        $form->setVar('_submitValues', $submit);
        self::assertEmpty(CRM_Consentactivity_Service::postProcess(CRM_Profile_Form_Edit::class, $form), 'PostProcess supposed to be empty.');
        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));
        // Tag has to be removed.
        $tags = EntityTag::get(false)
            ->addWhere('entity_table', '=', 'civicrm_contact')
            ->addWhere('entity_id', '=', $contact['id'])
            ->addWhere('tag_id', '=', $cfg['tag-id'])
            ->execute();
        self::assertSame(0, count($tags));
    }
    /*
     * The previously created checkbox will be returned.
     */
    public function testCustomCheckboxFields()
    {
        $customGroup = CustomGroup::create(false)
            ->addValue('title', 'Test custom group v1')
            ->addValue('extends', 'Contact')
            ->addValue('is_active', 1)
            ->addValue('is_public', 1)
            ->addValue('style', 'Inline')
            ->execute()
            ->first();
        $optionGroup = OptionGroup::create(false)
            ->addValue('title', 'Test option group v1')
            ->addValue('name', 'Test option group v1')
            ->addValue('data_type', 'String')
            ->addValue('is_public', 1)
            ->execute()
            ->first();
        OptionValue::create(false)
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('label', 'Value label v1')
            ->addValue('value', '1')
            ->addValue('weight', '1')
            ->execute();
        $customField = CustomField::create(false)
            ->addValue('custom_group_id', $customGroup['id'])
            ->addValue('label', 'Field label v1')
            ->addValue('data_type', 'String')
            ->addValue('html_type', 'CheckBox')
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('options_per_line', '1')
            ->execute()
            ->first();
        $group = Group::create(false)
            ->addValue('title', 'title')
            ->addValue('visibility', 'Public Pages')
            ->addValue('group_type', 'Mailing List')
            ->addValue('created_id', 1)
            ->execute()
            ->first();
        $params = CRM_Consentactivity_Service::customCheckboxFields();
        self::assertSame(1, count($params));
    }
    public function testPostProcessWithCustomFields()
    {
        $customGroup = CustomGroup::create(false)
            ->addValue('title', 'Test custom group')
            ->addValue('extends', 'Contact')
            ->addValue('is_active', 1)
            ->addValue('is_public', 1)
            ->addValue('style', 'Inline')
            ->execute()
            ->first();
        $optionGroup = OptionGroup::create(false)
            ->addValue('title', 'Test option group')
            ->addValue('name', 'Test option group')
            ->addValue('data_type', 'String')
            ->addValue('is_public', 1)
            ->execute()
            ->first();
        OptionValue::create(false)
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('label', 'Value label')
            ->addValue('value', '1')
            ->addValue('weight', '1')
            ->execute();
        $customField = CustomField::create(false)
            ->addValue('custom_group_id', $customGroup['id'])
            ->addValue('label', 'Field label')
            ->addValue('data_type', 'String')
            ->addValue('html_type', 'CheckBox')
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('options_per_line', '1')
            ->execute()
            ->first();
        $group = Group::create(false)
            ->addValue('title', 'title')
            ->addValue('visibility', 'Public Pages')
            ->addValue('group_type', 'Mailing List')
            ->addValue('created_id', 1)
            ->execute()
            ->first();
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $cfg['custom-field-map'][] = [
            'custom-field-id' => $customField['id'],
            'consent-field-id' => 'do_not_phone',
            'group-id' => $group['id'],
        ];
        $config->update($cfg);
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->addValue('do_not_phone', true)
            ->execute()
            ->first();
        $form = new CRM_Profile_Form_Edit();
        $form->setVar('_id', $contact['id']);
        $submit = [
            $customField['id'] => [1 => '1'],
            'last_name' => 'name',
        ];
        $form->setVar('_submitValues', $submit);
        self::assertEmpty(CRM_Consentactivity_Service::postProcess(CRM_Profile_Form_Edit::class, $form), 'PostProcess supposed to be empty.');
    }
    public function testPostProcessWithCustomFieldsUpdateGroupStatus()
    {
        $customGroup = CustomGroup::create(false)
            ->addValue('title', 'Test custom group v3')
            ->addValue('extends', 'Contact')
            ->addValue('is_active', 1)
            ->addValue('is_public', 1)
            ->addValue('style', 'Inline')
            ->execute()
            ->first();
        $customGroupOther = CustomGroup::create(false)
            ->addValue('title', 'Test custom group v4')
            ->addValue('extends', 'Contact')
            ->addValue('is_active', 1)
            ->addValue('is_public', 1)
            ->addValue('style', 'Inline')
            ->execute()
            ->first();
        $optionGroup = OptionGroup::create(false)
            ->addValue('title', 'Test option group v3')
            ->addValue('name', 'Test option group v3')
            ->addValue('data_type', 'String')
            ->addValue('is_public', 1)
            ->execute()
            ->first();
        OptionValue::create(false)
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('label', 'Value label v3')
            ->addValue('value', '1')
            ->addValue('weight', '1')
            ->execute();
        $optionGroupOther = OptionGroup::create(false)
            ->addValue('title', 'Test option group v4')
            ->addValue('name', 'Test option group v4')
            ->addValue('data_type', 'String')
            ->addValue('is_public', 1)
            ->execute()
            ->first();
        OptionValue::create(false)
            ->addValue('option_group_id', $optionGroupOther['id'])
            ->addValue('label', 'Value label v4')
            ->addValue('value', '1')
            ->addValue('weight', '1')
            ->execute();
        $customField = CustomField::create(false)
            ->addValue('custom_group_id', $customGroup['id'])
            ->addValue('label', 'Field label v3')
            ->addValue('data_type', 'String')
            ->addValue('html_type', 'CheckBox')
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('options_per_line', '1')
            ->execute()
            ->first();
        $customFieldOther = CustomField::create(false)
            ->addValue('custom_group_id', $customGroupOther['id'])
            ->addValue('label', 'Field label v4')
            ->addValue('data_type', 'String')
            ->addValue('html_type', 'CheckBox')
            ->addValue('option_group_id', $optionGroupOther['id'])
            ->addValue('options_per_line', '1')
            ->execute()
            ->first();
        $group = Group::create(false)
            ->addValue('title', 'title')
            ->addValue('visibility', 'Public Pages')
            ->addValue('group_type', 'Mailing List')
            ->addValue('created_id', 1)
            ->execute()
            ->first();
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $cfg['custom-field-map'][] = [
            'custom-field-id' => $customField['id'],
            'consent-field-id' => 'do_not_phone',
            'group-id' => $group['id'],
        ];
        $cfg['custom-field-map'][] = [
            'custom-field-id' => $customFieldOther['id'],
            'consent-field-id' => 'do_not_email',
            'group-id' => $group['id'],
        ];
        $config->update($cfg);
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->addValue('do_not_phone', true)
            ->execute()
            ->first();
        GroupContact::create(false)
            ->addValue('contact_id', $contact['id'])
            ->addValue('group_id', $group['id'])
            ->addValue('status', 'Removed')
            ->execute();
        $form = new CRM_Profile_Form_Edit();
        $form->setVar('_id', $contact['id']);
        $submit = [
            $customField['id'] => [1 => '1'],
            'last_name' => 'name',
        ];
        $form->setVar('_submitValues', $submit);
        self::assertEmpty(CRM_Consentactivity_Service::postProcess(CRM_Profile_Form_Edit::class, $form), 'PostProcess supposed to be empty.');
    }
    public function testAnonymizeContact()
    {
        // contact
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->addValue('first_name', 'first')
            ->addValue('last_name', 'last')
            ->addValue('middle_name', 'middle')
            ->addValue('display_name', 'display')
            ->addValue('email_greeting_display', 'display')
            ->addValue('postal_greeting_display', 'display')
            ->addValue('addressee_display', 'display')
            ->addValue('nick_name', 'nick')
            ->addValue('sort_name', 'sort')
            ->addValue('external_identifier', 'ext')
            ->addValue('image_url', 'http://internet.com/me.png')
            ->addValue('api_key', 'apk')
            ->addValue('birth_date', '2000-01-01')
            ->addValue('deceased_date', '2000-01-02')
            ->addValue('job_title', 'boss')
            ->addValue('gender_id', 1)
            ->addValue('gender_id', 1)
            ->execute()
            ->first();
        // address
        Address::create(false)
            ->addValue('location_type_id', 1)
            ->addValue('contact_id', $contact['id'])
            ->addValue('street_address', 'sss')
            ->execute();
        // email
        Email::create(false)
            ->addValue('location_type_id', 1)
            ->addValue('contact_id', $contact['id'])
            ->addValue('email', '01@email.com')
            ->execute();
        // phone
        Phone::create(false)
            ->addValue('location_type_id', 1)
            ->addValue('contact_id', $contact['id'])
            ->addValue('phone', '911')
            ->execute();
        // instant message
        IM::create(false)
            ->addValue('location_type_id', 1)
            ->addValue('contact_id', $contact['id'])
            ->addValue('name', 'instant@message.com')
            ->addValue('provider_id', 4)
            ->execute();
        // website
        Website::create(false)
            ->addValue('contact_id', $contact['id'])
            ->addValue('url', 'http://internet.com/me.html')
            ->addValue('website_type_id', 2)
            ->execute();
        // call anonimization
        self::assertEmpty(CRM_Consentactivity_Service::anonymizeContact($contact['id']));
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
            'first_name', 'last_name', 'middle_name', 'display_name',
            'nick_name', 'sort_name',
            'external_identifier', 'api_key', 'birth_date',
            'deceased_date', 'employer_id', 'job_title', 'gender_id',
        ];
        foreach ($contactFieldsThatNeedsToBeDeleted as $field) {
            self::assertEmpty($updatedContact[$field], 'The '.$field.' field should be empty, but it is '.$updatedContact[$field]);
        }
        $privacyFieldsThatNeedsToBeSet = [
            'do_not_email', 'do_not_phone', 'do_not_mail',
            'do_not_sms', 'do_not_trade', 'is_opt_out',
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
    }
    /*
     * It tests the post function.
     */
    public function testPostNotRelevantData()
    {
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = true;
        $cfg->update($config);
        $refObject = (object)['is_test'=>false, 'receive_date'=>'2020010112131400', 'contact_id' => $contact['id']];

        self::assertEmpty(CRM_Consentactivity_Service::post('delete', 'Contribution', 1, $refObject));
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);

        self::assertEmpty(CRM_Consentactivity_Service::post('create', 'Contact', 1, $refObject));
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);

        $refObject->is_test = true;
        self::assertEmpty(CRM_Consentactivity_Service::post('create', 'Contribution', 1, $refObject));
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);
    }
    public function testPostConfigNotSet()
    {
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = false;
        $cfg->update($config);
        $refObject = (object)['is_test'=>false, 'receive_date'=>'2020010112131400', 'contact_id' => $contact['id']];

        self::assertEmpty(CRM_Consentactivity_Service::post('create', 'Contribution', 1, $refObject));
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);
    }
    public function testPostOldReceiveDate()
    {
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = true;
        $cfg->update($config);
        $before = $config['consent-expiration-years'];
        $before += 2;
        $refObject = (object)['is_test'=>false, 'receive_date'=>date('YmdHis', strtotime($before.' years ago')), 'contact_id' => $contact['id']];

        self::assertEmpty(CRM_Consentactivity_Service::post('create', 'Contribution', 1, $refObject));
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);
    }
    public function testPostTriggerActivity()
    {
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = true;
        $cfg->update($config);
        $refObject = (object)['is_test'=>false, 'receive_date'=>date('YmdHis'), 'contact_id' => $contact['id']];

        self::assertEmpty(CRM_Consentactivity_Service::post('create', 'Contribution', 1, $refObject));
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal)+1, $activities);
    }
}
