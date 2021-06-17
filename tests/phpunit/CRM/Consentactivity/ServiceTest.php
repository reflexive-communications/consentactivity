<?php

use Civi\Api4\OptionValue;
use Civi\Api4\Contact;
use Civi\Api4\Activity;

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
        self::assertSame(0, count($activities));
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
}
