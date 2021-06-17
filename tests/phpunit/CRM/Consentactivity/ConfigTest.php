<?php

/**
 * Config class base test cases.
 *
 * @group headless
 */
class CRM_Consentactivity_ConfigTest extends CRM_Consentactivity_HeadlessBase
{
    /**
     * It checks that the create function works well.
     */
    public function testCreate()
    {
        $config = new CRM_Consentactivity_Config('consentactivity_test');
        self::assertTrue($config->create(), 'Create config has to be successful.');
        $cfg = $config->get();
        self::assertTrue(array_key_exists('activity-type-id', $cfg), 'activity-type-id key is missing from the config.');
        self::assertSame(0, $cfg['activity-type-id'], 'Invalid activity-type-id initial value.');
        self::assertTrue(array_key_exists('option-value-id', $cfg), 'option-value-id key is missing from the config.');
        self::assertSame(0, $cfg['option-value-id'], 'Invalid option-value-id initial value.');
        self::assertTrue($config->create(), 'Create config has to be successful multiple times.');
    }
}
