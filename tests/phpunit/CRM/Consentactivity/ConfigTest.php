<?php

use Civi\Consentactivity\HeadlessTestCase;

/**
 * Config class base test cases.
 *
 * @group headless
 */
class CRM_Consentactivity_ConfigTest extends HeadlessTestCase
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
        self::assertTrue(array_key_exists('saved-search-id', $cfg), 'saved-search-id key is missing from the config.');
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID, $cfg['saved-search-id'], 'Invalid saved-search-id initial value.');
        self::assertTrue(array_key_exists('tagging-search-id', $cfg), 'tagging-search-id key is missing from the config.');
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID, $cfg['tagging-search-id'], 'Invalid tagging-search-id initial value.');
        self::assertTrue(array_key_exists('tag-id', $cfg), 'tag-id key is missing from the config.');
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_TAG_ID, $cfg['tag-id'], 'Invalid tag-id initial value.');
        self::assertTrue(array_key_exists('expired-tag-id', $cfg), 'expired-tag-id key is missing from the config.');
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_EXPIRED_TAG_ID, $cfg['expired-tag-id'], 'Invalid expired-tag-id initial value.');
        self::assertTrue(array_key_exists('consent-after-contribution', $cfg), 'consent-after-contribution key is missing from the config.');
        self::assertSame(false, $cfg['consent-after-contribution'], 'Invalid consent-after-contribution key initial value.');
        self::assertTrue(array_key_exists('consent-expiration-years', $cfg), 'consent-expiration-years key is missing from the config.');
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_YEAR, $cfg['consent-expiration-years'], 'Invalid consent-expiration-years initial value.');
        self::assertTrue(array_key_exists('consent-expiration-tagging-days', $cfg), 'consent-expiration-tagging-days key is missing from the config.');
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_TAGGING_DAYS, $cfg['consent-expiration-tagging-days'], 'Invalid consent-expiration-tagging-days initial value.');
        self::assertTrue(array_key_exists('custom-field-map', $cfg), 'custom-field-map key is missing from the config.');
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_CUSTOM_FIELD_MAP, $cfg['custom-field-map'], 'Invalid custom-field-map initial value.');
        self::assertTrue($config->create(), 'Create config has to be successful multiple times.');
    }
}
