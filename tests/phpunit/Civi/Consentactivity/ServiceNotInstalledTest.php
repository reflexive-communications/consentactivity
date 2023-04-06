<?php

namespace Civi\Consentactivity;

use Civi\Api4\Contact;
use Civi\Test;
use CRM_Profile_Form_Edit;

/**
 * @group headless
 */
class ServiceNotInstalledTest extends HeadlessTestCase
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
        $activityType = Service::createDefaultActivityType();
        self::assertIsArray($activityType);
        self::assertSame(Service::DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL, $activityType['label']);
        self::assertSame(Service::DEFAULT_CONSENT_ACTIVITY_TYPE_ICON, $activityType['icon']);
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
        self::assertEmpty(Service::postProcess('Not_Intrested_In_This_Class', $form), 'PostProcess supposed to be empty.');
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchExpired()
    {
        $activityType = Service::createDefaultActivityType();
        $search = Service::savedSearchExpired($activityType['name'], 1, 1, false);
        self::assertSame('Contact', $search['api_entity']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchExpiredUpdate()
    {
        $activityType = Service::createDefaultActivityType();
        $search = Service::savedSearchExpired($activityType['name'], 1, 1, false);
        self::assertSame('Contact', $search['api_entity']);
        $search = Service::savedSearchExpiredUpdate($activityType['name'], 1, 1, $search['id'], false);
        self::assertSame('Contact', $search['api_entity']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchTagging()
    {
        $activityType = Service::createDefaultActivityType();
        $search = Service::savedSearchTagging($activityType['name'], 1, false);
        self::assertSame('Contact', $search['api_entity']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testSavedSearchTaggingUpdate()
    {
        $activityType = Service::createDefaultActivityType();
        $search = Service::savedSearchTagging($activityType['name'], 1, false);
        self::assertSame('Contact', $search['api_entity']);
        $search = Service::savedSearchTaggingUpdate($activityType['name'], 1, $search['id'], false);
        self::assertSame('Contact', $search['api_entity']);
    }
}
