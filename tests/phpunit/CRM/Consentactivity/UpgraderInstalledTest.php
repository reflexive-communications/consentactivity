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
    }
}
