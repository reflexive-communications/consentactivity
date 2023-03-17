<?php

use Civi\Api4\Contact;
use Civi\Consentactivity\HeadlessTestCase;
use Civi\Test;

/**
 * @group headless
 */
class CRM_Consentactivity_ServiceNotInstalledTest extends HeadlessTestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        Test::headless()
            ->install('rc-base')
            ->apply(true);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testCreateDefaultActivityType()
    {
        $activityType = CRM_Consentactivity_Service::createDefaultActivityType();
        self::assertIsArray($activityType);
        self::assertSame(CRM_Consentactivity_Service::DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL, $activityType['label']);
        self::assertSame(CRM_Consentactivity_Service::DEFAULT_CONSENT_ACTIVITY_TYPE_ICON, $activityType['icon']);
        self::assertTrue($activityType['is_active']);
        self::assertTrue($activityType['is_reserved']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcessInvalidFormName()
    {
        $form = new CRM_Profile_Form_Edit();
        $contact = Contact::create(false)
            ->addValue('contact_type', 'Individual')
            ->execute()
            ->first();
        $form->setVar('_id', $contact['id']);
        $submit = ['is_opt_out' => ''];
        $form->setVar('_submitValues', $submit);
        self::assertEmpty(CRM_Consentactivity_Service::postProcess('Not_Intrested_In_This_Class', $form), 'PostProcess supposed to be empty.');
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchExpired()
    {
        $activityType = CRM_Consentactivity_Service::createDefaultActivityType();
        $search = CRM_Consentactivity_Service::savedSearchExpired($activityType['name'], 1, 1, false);
        self::assertSame('Contact', $search['api_entity']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchExpiredUpdate()
    {
        $activityType = CRM_Consentactivity_Service::createDefaultActivityType();
        $search = CRM_Consentactivity_Service::savedSearchExpired($activityType['name'], 1, 1, false);
        self::assertSame('Contact', $search['api_entity']);
        $search = CRM_Consentactivity_Service::savedSearchExpiredUpdate($activityType['name'], 1, 1, $search['id'], false);
        self::assertSame('Contact', $search['api_entity']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchTagging()
    {
        $activityType = CRM_Consentactivity_Service::createDefaultActivityType();
        $search = CRM_Consentactivity_Service::savedSearchTagging($activityType['name'], 1, false);
        self::assertSame('Contact', $search['api_entity']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchTaggingUpdate()
    {
        $activityType = CRM_Consentactivity_Service::createDefaultActivityType();
        $search = CRM_Consentactivity_Service::savedSearchTagging($activityType['name'], 1, false);
        self::assertSame('Contact', $search['api_entity']);
        $search = CRM_Consentactivity_Service::savedSearchTaggingUpdate($activityType['name'], 1, $search['id'], false);
        self::assertSame('Contact', $search['api_entity']);
    }
}
