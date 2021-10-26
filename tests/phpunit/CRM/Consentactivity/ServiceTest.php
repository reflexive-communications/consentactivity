<?php

use Civi\Api4\OptionValue;
use Civi\Api4\Contact;
use Civi\Api4\Activity;
use Civi\Api4\EntityTag;
use CRM_Consentactivity_ExtensionUtil as E;

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
}
