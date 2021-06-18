<?php

use Civi\Api4\OptionValue;
use Civi\Api4\Contact;
use Civi\Api4\Activity;

/**
 * Service class test cases.
 *
 * @group headless
 */
class CRM_Consentactivity_ServiceNotInstalledTest extends CRM_Consentactivity_HeadlessBase
{
    /*
     * Overwrite setup function to skip the install of the current extenstion
     * to be able to test the create steps of the service.
     */
    public function setUpHeadless()
    {
        return \Civi\Test::headless()
            ->install('rc-base')
            ->apply();
    }

    /**
     * Test the createDefaultActivityType function.
     */
    public function testCreateDefaultActivityType()
    {
        $activityType = CRM_Consentactivity_Service::createDefaultActivityType();
        self::assertIsArray($activityType);
        self::assertSame(CRM_Consentactivity_Service::DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL, $activityType['label']);
        self::assertSame(CRM_Consentactivity_Service::DEFAULT_CONSENT_ACTIVITY_TYPE_ICON, $activityType['icon']);
        self::assertTrue($activityType['is_active']);
        self::assertTrue($activityType['is_reserved']);
        // when we want to create the type again without changing the label, it will return the same;
        $newActivityType = CRM_Consentactivity_Service::createDefaultActivityType();
        // If the existing one is returned from this function, the list of the keys is
        // extended, so that only those keys could be checked that were set in the original.
        foreach ($activityType as $k => $v) {
            self::assertSame($v, $newActivityType[$k]);
        }
        // Update the option value manually, then create again. The values has to be set back.
        $result = OptionValue::update(false)
            ->addWhere('id', '=', $activityType['id'])
            ->addValue('is_active', false)
            ->addValue('is_reserved', false)
            ->execute()
            ->first();
        self::assertFalse($result['is_active']);
        self::assertFalse($result['is_reserved']);
        $newActivityType = CRM_Consentactivity_Service::createDefaultActivityType();
        self::assertSame($activityType['id'], $newActivityType['id']);
        $newActivityType = CRM_Consentactivity_Service::getActivityType($newActivityType['id']);
        foreach ($activityType as $k => $v) {
            self::assertSame($v, $newActivityType[$k]);
        }
    }

    /**
     * Test the postProcess function.
     */
    public function testPostProcessInvalidFormName()
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
        self::assertEmpty(CRM_Consentactivity_Service::postProcess('Not_Intrested_In_This_Class', $form), 'PostProcess supposed to be empty.');
    }
}
