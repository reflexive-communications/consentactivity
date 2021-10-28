<?php

use CRM_Consentactivity_ExtensionUtil as E;

/**
 * Tests for the Upgrader process.
 *
 * @group headless
 */
class CRM_Consentactivity_UpgraderInstalledTest extends CRM_Consentactivity_HeadlessBase
{

    /**
     * Test the upgrade_5000 process.
     */
    public function testUpgrade5000()
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        unset($cfg['saved-search-id']);
        $config->update($cfg);
        $installer = new CRM_Consentactivity_Upgrader(E::LONG_NAME, ".");
        try {
            $this->assertTrue($installer->upgrade_5000());
        } catch (Exception $e) {
            $this->fail("Should not throw exception. ".$e->getMessage());
        }
        // should do nothing.
        $config->load();
        $cfgNew = $config->get();
        self::assertSame($cfg, $cfgNew);
    }
    /**
     * Test the upgrade_5100 process.
     */
    public function testUpgrade5100()
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        unset($cfg['tag-id']);
        unset($cfg['tagging-search-id']);
        unset($cfg['consent-expiration-years']);
        unset($cfg['consent-expiration-tagging-days']);
        $cfg['saved-search-id'] = 10;

        $config->update($cfg);
        $installer = new CRM_Consentactivity_Upgrader(E::LONG_NAME, ".");
        try {
            $this->assertTrue($installer->upgrade_5100());
        } catch (Exception $e) {
            $this->fail("Should not throw exception. ".$e->getMessage());
        }
        $config->load();
        $cfg = $config->get();
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID, $cfg['saved-search-id']);
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID, $cfg['tagging-search-id']);
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_TAG_ID, $cfg['tag-id']);
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_YEAR, $cfg['consent-expiration-years']);
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_TAGGING_DAYS, $cfg['consent-expiration-tagging-days']);
    }
    /**
     * Test the upgrade_5101 process.
     */
    public function testUpgrade5101()
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        unset($cfg['custom-field-map']);

        $config->update($cfg);
        $installer = new CRM_Consentactivity_Upgrader(E::LONG_NAME, ".");
        self::assertTrue($installer->upgrade_5101());
        $config->load();
        $cfg = $config->get();
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_CUSTOM_FIELD_MAP, $cfg['custom-field-map']);
    }
}
