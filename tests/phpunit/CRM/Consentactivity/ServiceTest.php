<?php

/**
 * FIXME - Add test description.
 *
 * @group headless
 */
class CRM_Consentactivity_ServiceTest extends CRM_Consentactivity_HeadlessBase
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
    }
}
