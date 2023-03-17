<?php

use Civi\Consentactivity\HeadlessTestCase;

/**
 * @group headless
 */
class CRM_Consentactivity_UpgraderTest extends HeadlessTestCase
{
    /**
     * @return void
     */
    public function testInstall()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        try {
            $this->assertEmpty($installer->install());
        } catch (Exception $e) {
            $this->fail('Should not throw exception.');
        }
    }

    /**
     * @return void
     */
    public function testEnableNoIssue()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        try {
            $this->assertEmpty($installer->install());
            $this->assertEmpty($installer->enable());
        } catch (Exception $e) {
            $this->fail('Should not throw exception. '.$e->getMessage());
        }
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testEnableOldVersionOptionValue()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        $this->assertEmpty($installer->install());
        $this->assertEmpty($installer->enable());
        $cfg = new CRM_Consentactivity_Config(CRM_Consentactivity_ExtensionUtil::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['option-value-id'] = $config['option-value-id'] + 1;
        $cfg->update($config);
        try {
            $this->assertEmpty($installer->enable());
        } catch (Exception $e) {
            $this->fail('Should not throw exception. '.$e->getMessage());
        }
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testEnableOldVersionTagNotSet()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        $this->assertEmpty($installer->install());
        $this->assertEmpty($installer->enable());
        $cfg = new CRM_Consentactivity_Config(CRM_Consentactivity_ExtensionUtil::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        unset($config['tag-id']);
        unset($config['tagging-search-id']);
        unset($config['consent-expiration-years']);
        unset($config['consent-expiration-tagging-days']);
        $cfg->update($config);
        try {
            $this->assertEmpty($installer->enable());
        } catch (Exception $e) {
            $this->fail('Should not throw exception. '.$e->getMessage());
        }
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testEnableOldVersionTagDeleted()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        $this->assertEmpty($installer->install());
        $this->assertEmpty($installer->enable());
        $cfg = new CRM_Consentactivity_Config(CRM_Consentactivity_ExtensionUtil::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['tag-id'] = '100000';
        $cfg->update($config);
        try {
            $this->assertEmpty($installer->enable());
        } catch (Exception $e) {
            $this->fail('Should not throw exception. '.$e->getMessage());
        }
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testEnableOldVersionDeletedTagSearchDeletion()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        $this->assertEmpty($installer->install());
        $this->assertEmpty($installer->enable());
        $cfg = new CRM_Consentactivity_Config(CRM_Consentactivity_ExtensionUtil::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['tag-id'] = '100000';
        $config['saved-search-id'] = 10;
        $config['tagging-search-id'] = 11;
        $cfg->update($config);
        try {
            $this->assertEmpty($installer->enable());
        } catch (Exception $e) {
            $this->fail('Should not throw exception. '.$e->getMessage());
        }
        $cfg->load();
        $config = $cfg->get();
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID, $config['saved-search-id']);
        self::assertSame(CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID, $config['tagging-search-id']);
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testEnableOldVersionValidTagDeletedSearch()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        $this->assertEmpty($installer->install());
        $this->assertEmpty($installer->enable());
        $cfg = new CRM_Consentactivity_Config(CRM_Consentactivity_ExtensionUtil::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['tag-id'] = '1';
        $config['expired-tag-id'] = '2';
        $config['saved-search-id'] = 10;
        $config['tagging-search-id'] = 11;
        $cfg->update($config);
        try {
            $this->assertEmpty($installer->enable());
        } catch (Exception $e) {
            $this->fail('Should not throw exception. '.$e->getMessage());
        }
        $cfg->load();
        $config = $cfg->get();
        self::assertNotSame(10, $config['saved-search-id']);
        self::assertNotSame(11, $config['tagging-search-id']);
        self::assertNotSame(CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID, $config['saved-search-id']);
        self::assertNotSame(CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID, $config['tagging-search-id']);
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testUninstall()
    {
        $installer = new CRM_Consentactivity_Upgrader();
        $this->assertEmpty($installer->install());
        try {
            $this->assertEmpty($installer->uninstall());
        } catch (Exception $e) {
            $this->fail('Should not throw exception. '.$e->getMessage());
        }
    }
}
