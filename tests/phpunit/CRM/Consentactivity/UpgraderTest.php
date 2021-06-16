<?php

/**
 * Tests for the Upgrader process.
 *
 * @group headless
 */
class CRM_Consentactivity_UpgraderTest extends CRM_Consentactivity_HeadlessBase
{
    /**
     * Test the install process.
     */
    public function testInstall()
    {
        $installer = new CRM_Consentactivity_Upgrader("consentactivity_test", ".");
        try {
            $this->assertEmpty($installer->install());
        } catch (Exception $e) {
            $this->fail("Should not throw exception.");
        }
    }

    /**
     * Test the postInstall process.
     */
    public function testPostInstall()
    {
        $installer = new CRM_Consentactivity_Upgrader("consentactivity_test", ".");
        try {
            $this->assertEmpty($installer->install());
            $this->assertEmpty($installer->postInstall());
        } catch (Exception $e) {
            $this->fail("Should not throw exception.");
        }
    }

    /**
     * Test the enable process.
     */
    public function testEnable()
    {
        $installer = new CRM_Consentactivity_Upgrader("consentactivity_test", ".");
        try {
            $this->assertEmpty($installer->install());
            $this->assertEmpty($installer->postInstall());
            $this->assertEmpty($installer->enable());
        } catch (Exception $e) {
            $this->fail("Should not throw exception.");
        }
    }

    /**
     * Test the uninstall process.
     */
    public function testUninstall()
    {
        $installer = new CRM_Consentactivity_Upgrader("consentactivity_test", ".");
        $this->assertEmpty($installer->install());
        try {
            $this->assertEmpty($installer->uninstall());
        } catch (Exception $e) {
            $this->fail("Should not throw exception.");
        }
    }
}
