<?php

use Civi\Consentactivity\HeadlessTestCase;
use CRM_Consentactivity_ExtensionUtil as E;

/**
 * @group headless
 */
class CRM_Consentactivity_Form_SettingsTest extends HeadlessTestCase
{
    /**
     * @return array
     */
    private static function testDefaultSetting(): array
    {
        return [
            'activity-type-id' => 0,
            'option-value-id' => 0,
            'saved-search-id' => CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID,
            'tagging-search-id' => CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID,
            'tag-id' => CRM_Consentactivity_Config::DEFAULT_TAG_ID,
            'expired-tag-id' => CRM_Consentactivity_Config::DEFAULT_EXPIRED_TAG_ID,
            'consent-after-contribution' => false,
            'consent-expiration-years' => CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_YEAR,
            'consent-expiration-tagging-days' => CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_TAGGING_DAYS,
            'custom-field-map' => CRM_Consentactivity_Config::DEFAULT_CUSTOM_FIELD_MAP,
            'landing-page' => CRM_Consentactivity_Config::DEFAULT_LANDING_PAGE,
        ];
    }

    /**
     * @return void
     */
    private function setupTestDefaultConfig()
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        self::assertTrue($config->update(self::testDefaultSetting()), 'Config update has to be successful.');
    }

    /**
     * @return void
     */
    public function testPreProcessExistingConfig()
    {
        $this->setupTestDefaultConfig();
        $form = new CRM_Consentactivity_Form_Settings();
        try {
            self::assertEmpty($form->preProcess(), 'PreProcess supposed to be empty.');
        } catch (Exception $e) {
            self::fail('Shouldn\'t throw exception with valid db. '.$e->getMessage());
        }
    }

    /**
     * PreProcess test case with deleted config.
     * Setup test configuration then call the function.
     * It should throw exception.
     */
    public function testPreProcessMissingConfig()
    {
        $form = new CRM_Consentactivity_Form_Settings();
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->remove();
        self::expectException(CRM_Core_Exception::class);
        self::expectExceptionMessage(E::LONG_NAME.'_config config invalid.');
        self::assertEmpty($form->preProcess(), 'PreProcess supposed to be empty.');
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testSetDefaultValues()
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $testSettings = self::testDefaultSetting();
        $testSettings['custom-field-map'][] = [
            'custom-field-id' => '1',
            'consent-field-id' => '1',
            'group-id' => '0',
        ];
        $config->update($testSettings);
        $form = new CRM_Consentactivity_Form_Settings();
        $form->preProcess();
        $defaults = $form->setDefaultValues();
        self::assertSame($testSettings['tag-id'], $defaults['tagId']);
        self::assertSame($testSettings['consent-expiration-years'], $defaults['consentExpirationYears']);
        self::assertSame($testSettings['consent-expiration-tagging-days'], $defaults['consentExpirationTaggingDays']);
        self::assertSame($testSettings['landing-page'], $defaults['landing_page']);
        self::assertSame($testSettings['custom-field-map'][0]['custom-field-id'], $defaults['map_custom_field_id_0']);
        self::assertSame($testSettings['custom-field-map'][0]['consent-field-id'], $defaults['map_consent_field_id_0']);
        self::assertSame($testSettings['custom-field-map'][0]['group-id'], $defaults['map_group_id_0']);
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testAddRules()
    {
        $this->setupTestDefaultConfig();
        $form = new CRM_Consentactivity_Form_Settings();
        self::assertEmpty($form->preProcess());
        self::assertEmpty($form->buildQuickForm());
        try {
            self::assertEmpty($form->addRules(), 'addRules supposed to be empty.');
        } catch (Exception $e) {
            self::fail('Shouldn\'t throw exception with valid db. '.$e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testZeroNotAllowed()
    {
        $this->setupTestDefaultConfig();
        $form = new CRM_Consentactivity_Form_Settings();
        $testData = [
            [['consentExpirationYears' => '1', 'consentExpirationTaggingDays' => '1'], true],
            [['consentExpirationYears' => '0', 'consentExpirationTaggingDays' => '1'], ['consentExpirationYears' => E::ts('Not allowed value.')]],
            [
                ['consentExpirationYears' => '0', 'consentExpirationTaggingDays' => '0'],
                ['consentExpirationYears' => E::ts('Not allowed value.'), 'consentExpirationTaggingDays' => E::ts('Not allowed value.')],
            ],
            [['consentExpirationYears' => '1', 'consentExpirationTaggingDays' => '0'], ['consentExpirationTaggingDays' => E::ts('Not allowed value.')]],
        ];
        foreach ($testData as $t) {
            self::assertSame($t[1], CRM_Consentactivity_Form_Settings::zeroNotAllowed($t[0]));
        }
    }

    /**
     * @return void
     */
    public function testSameTagsNotAllowed()
    {
        $this->setupTestDefaultConfig();
        $form = new CRM_Consentactivity_Form_Settings();
        $testData = [
            [['tagId' => '1', 'expiredTagId' => '1'], ['tagId' => E::ts('Duplication.'), 'expiredTagId' => E::ts('Duplication.')]],
            [['tagId' => '1', 'expiredTagId' => '2'], true],
        ];
        foreach ($testData as $t) {
            self::assertSame($t[1], CRM_Consentactivity_Form_Settings::sameTagsNotAllowed($t[0]));
        }
    }

    /**
     * @return void
     */
    public function testCustomFieldDuplicationNotAllowed()
    {
        $this->setupTestDefaultConfig();
        $form = new CRM_Consentactivity_Form_Settings();
        $testData = [
            [['map_custom_field_id_0' => '1', 'map_custom_field_id_1' => '2'], true],
            [['map_custom_field_id_0' => '1', 'map_custom_field_id_1' => '1'], ['map_custom_field_id_0' => E::ts('Duplication.')]],
            [
                ['map_custom_field_id_0' => '1', 'map_custom_field_id_1' => '2', 'map_custom_field_id_2' => '1', 'map_custom_field_id_3' => '2'],
                ['map_custom_field_id_0' => E::ts('Duplication.'), 'map_custom_field_id_1' => E::ts('Duplication.')],
            ],
        ];
        foreach ($testData as $t) {
            self::assertSame($t[1], CRM_Consentactivity_Form_Settings::customFieldDuplicationNotAllowed($t[0]));
        }
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function testBuildQuickFormNoActionState()
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $testSettings = self::testDefaultSetting();
        $testSettings['custom-field-map'][] = [
            'custom-field-id' => '1',
            'consent-field-id' => '1',
            'group-id' => '0',
        ];
        $testSettings['custom-field-map'][] = [
            'custom-field-id' => '2',
            'consent-field-id' => '2',
            'group-id' => '1',
        ];
        $config->update($testSettings);
        $form = new CRM_Consentactivity_Form_Settings();
        self::assertEmpty($form->preProcess(), 'PreProcess supposed to be empty.');
        try {
            self::assertEmpty($form->buildQuickForm());
        } catch (Exception $e) {
            self::fail('It shouldn\'t throw exception. '.$e->getMessage());
        }
        self::assertSame(E::ts('Consentactivity Settings'), $form->getTitle(), 'Invalid form title.');
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostDefaultOriginalValues()
    {
        $_POST['tagId'] = '1';
        $_POST['expiredTagId'] = '2';
        $_POST['consentAfterContribution'] = '1';
        $_POST['consentExpirationYears'] = '2';
        $_POST['consentExpirationTaggingDays'] = '10';
        $_POST['landing_page'] = 'https://example.com';
        $this->setupTestDefaultConfig();
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $current = CRM_Consentactivity_Service::createDefaultActivityType();
        $cfg['activity-type-id'] = $current['value'];
        $cfg['option-value-id'] = $current['id'];
        $config->update($cfg);

        $form = new CRM_Consentactivity_Form_Settings();
        self::assertEmpty($form->preProcess(), 'PreProcess supposed to be empty.');
        try {
            self::assertEmpty($form->postProcess());
        } catch (Exception $e) {
            self::fail('It shouldn\'t throw exception. '.$e->getMessage());
        }
        $config->load();
        $cfg = $config->get();
        self::assertSame($_POST['tagId'], $cfg['tag-id']);
        self::assertSame($_POST['consentExpirationYears'], $cfg['consent-expiration-years']);
        self::assertSame($_POST['consentExpirationTaggingDays'], $cfg['consent-expiration-tagging-days']);
        self::assertSame($_POST['landing_page'], $cfg['landing-page']);
        self::assertNotSame(CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID, $cfg['saved-search-id']);
        self::assertNotSame(CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID, $cfg['tagging-search-id']);
        self::assertNotSame(CRM_Consentactivity_Config::DEFAULT_EXPIRED_TAG_ID, $cfg['expired-tag-id']);
        self::assertSame(true, $cfg['consent-after-contribution']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostDefaultSearchUpdate()
    {
        $_POST['tagId'] = '2';
        $_POST['expiredTagId'] = '3';
        $_POST['consentAfterContribution'] = '1';
        $_POST['consentExpirationYears'] = '2';
        $_POST['consentExpirationTaggingDays'] = '10';
        $_POST['landing_page'] = 'https://example.com';
        $this->setupTestDefaultConfig();
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $current = CRM_Consentactivity_Service::createDefaultActivityType();
        $cfg['activity-type-id'] = $current['value'];
        $cfg['option-value-id'] = $current['id'];
        $cfg['tag-id'] = 1;
        $cfg['expired-tag-id'] = 1;
        $cfg['saved-search-id'] = CRM_Consentactivity_Service::savedSearchExpired($current['name'], $cfg['tag-id'], $cfg['expired-tag-id'], false)['id'];
        $cfg['tagging-search-id'] = CRM_Consentactivity_Service::savedSearchTagging($current['name'], $cfg['expired-tag-id'], false)['id'];
        $config->update($cfg);

        $form = new CRM_Consentactivity_Form_Settings();
        self::assertEmpty($form->preProcess(), 'PreProcess supposed to be empty.');
        try {
            self::assertEmpty($form->postProcess());
        } catch (Exception $e) {
            self::fail('It shouldn\'t throw exception. '.$e->getMessage());
        }
        $config->load();
        $cfgNew = $config->get();
        self::assertSame($_POST['tagId'], $cfgNew['tag-id']);
        self::assertSame($_POST['expiredTagId'], $cfgNew['expired-tag-id']);
        self::assertSame(true, $cfgNew['consent-after-contribution']);
        self::assertSame($_POST['consentExpirationYears'], $cfgNew['consent-expiration-years']);
        self::assertSame($_POST['consentExpirationTaggingDays'], $cfgNew['consent-expiration-tagging-days']);
        self::assertSame($_POST['landing_page'], $cfgNew['landing-page']);
        self::assertSame($cfg['saved-search-id'], $cfgNew['saved-search-id']);
        self::assertSame($cfg['tagging-search-id'], $cfgNew['tagging-search-id']);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostMapping()
    {
        $_POST['tagId'] = '2';
        $_POST['expiredTagId'] = '3';
        $_POST['consentAfterContribution'] = '1';
        $_POST['consentExpirationYears'] = '2';
        $_POST['consentExpirationTaggingDays'] = '10';
        $_POST['map_custom_field_id_0'] = '1';
        $_POST['map_consent_field_id_0'] = '1';
        $_POST['map_group_id_0'] = '0';
        $_POST['map_custom_field_id_1'] = '2';
        $_POST['map_consent_field_id_1'] = '2';
        $_POST['map_group_id_1'] = '1';
        $_POST['landing_page'] = 'https://example.com';
        $this->setupTestDefaultConfig();
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $current = CRM_Consentactivity_Service::createDefaultActivityType();
        $cfg['activity-type-id'] = $current['value'];
        $cfg['option-value-id'] = $current['id'];
        $cfg['tag-id'] = 1;
        $cfg['expired-tag-id'] = 2;
        $cfg['saved-search-id'] = CRM_Consentactivity_Service::savedSearchExpired($current['name'], $cfg['tag-id'], $cfg['expired-tag-id'], false)['id'];
        $cfg['tagging-search-id'] = CRM_Consentactivity_Service::savedSearchTagging($current['name'], $cfg['expired-tag-id'], false)['id'];
        $config->update($cfg);

        $form = new CRM_Consentactivity_Form_Settings();
        self::assertEmpty($form->preProcess(), 'PreProcess supposed to be empty.');
        try {
            self::assertEmpty($form->postProcess());
        } catch (Exception $e) {
            self::fail('It shouldn\'t throw exception. '.$e->getMessage());
        }
        $config->load();
        $cfgNew = $config->get();
        self::assertSame($_POST['tagId'], $cfgNew['tag-id']);
        self::assertSame($_POST['expiredTagId'], $cfgNew['expired-tag-id']);
        self::assertSame(true, $cfgNew['consent-after-contribution']);
        self::assertSame($_POST['consentExpirationYears'], $cfgNew['consent-expiration-years']);
        self::assertSame($_POST['consentExpirationTaggingDays'], $cfgNew['consent-expiration-tagging-days']);
        self::assertSame($_POST['landing_page'], $cfgNew['landing-page']);
        self::assertSame($cfg['saved-search-id'], $cfgNew['saved-search-id']);
        self::assertSame($cfg['tagging-search-id'], $cfgNew['tagging-search-id']);
        self::assertSame($_POST['map_custom_field_id_0'], $cfgNew['custom-field-map'][0]['custom-field-id']);
        self::assertSame($_POST['map_custom_field_id_1'], $cfgNew['custom-field-map'][1]['custom-field-id']);
        self::assertSame($_POST['map_consent_field_id_0'], $cfgNew['custom-field-map'][0]['consent-field-id']);
        self::assertSame($_POST['map_consent_field_id_1'], $cfgNew['custom-field-map'][1]['consent-field-id']);
        self::assertSame($_POST['map_group_id_0'], $cfgNew['custom-field-map'][0]['group-id']);
        self::assertSame($_POST['map_group_id_1'], $cfgNew['custom-field-map'][1]['group-id']);
    }
}
