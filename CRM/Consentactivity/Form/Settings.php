<?php

use Civi\Consentactivity\Config;
use Civi\Consentactivity\Service;
use CRM_Consentactivity_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Consentactivity_Form_Settings extends CRM_Core_Form
{
    /**
     * Configdb
     *
     * @var Config
     */
    private $config;

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function preProcess(): void
    {
        // Get current settings
        $this->config = new Config(E::LONG_NAME);
        $this->config->load();
    }

    /**
     * @return array
     * @throws \CRM_Core_Exception
     */
    public function setDefaultValues(): array
    {
        $config = $this->config->get();
        // Set defaults
        $this->_defaults['tagId'] = $config['tag-id'];
        $this->_defaults['expiredTagId'] = $config['expired-tag-id'];
        $this->_defaults['consentAfterContribution'] = $config['consent-after-contribution'] ? '1' : '';
        $this->_defaults['consentExpirationYears'] = $config['consent-expiration-years'];
        $this->_defaults['consentExpirationTaggingDays'] = $config['consent-expiration-tagging-days'];
        $this->_defaults['landing_page'] = $config['landing-page'] ?? '';
        $this->_defaults['email_contact'] = $config['email-contact'] ?? '';
        if (isset($config['custom-field-map']) && count($config['custom-field-map']) > 0) {
            foreach ($config['custom-field-map'] as $k => $v) {
                $this->_defaults['map_custom_field_id_'.$k] = $v['custom-field-id'];
                $this->_defaults['map_consent_field_id_'.$k] = $v['consent-field-id'];
                $this->_defaults['map_group_id_'.$k] = $v['group-id'];
            }
        }

        return $this->_defaults;
    }

    /**
     * @return void
     */
    public function addRules(): void
    {
        $this->addRule('consentExpirationYears', E::ts('Expiration year has to be numeric.'), 'numeric', null, 'client');
        $this->addRule('consentExpirationYears', E::ts('Expiration year has to be numeric.'), 'numeric');
        $this->addRule('consentExpirationTaggingDays', E::ts('Tagging days has to be numeric.'), 'numeric', null, 'client');
        $this->addRule('consentExpirationTaggingDays', E::ts('Tagging days has to be numeric.'), 'numeric');
        $this->addFormRule(['CRM_Consentactivity_Form_Settings', 'zeroNotAllowed']);
        $this->addFormRule(['CRM_Consentactivity_Form_Settings', 'customFieldDuplicationNotAllowed']);
        $this->addFormRule(['CRM_Consentactivity_Form_Settings', 'sameTagsNotAllowed']);
    }

    /**
     * Here's our custom validation callback for rejecting
     * the 0 as value for the years or days.
     *
     * @param $values
     *
     * @return array|true
     */
    public static function zeroNotAllowed($values)
    {
        $errors = [];
        if ($values['consentExpirationYears'] === '0') {
            $errors['consentExpirationYears'] = E::ts('Not allowed value.');
        }
        if ($values['consentExpirationTaggingDays'] === '0') {
            $errors['consentExpirationTaggingDays'] = E::ts('Not allowed value.');
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * It rejects the duplications of the tags.
     *
     * @param $values
     *
     * @return array|true
     */
    public static function sameTagsNotAllowed($values)
    {
        $errors = [];
        if ($values['tagId'] === $values['expiredTagId']) {
            $errors['tagId'] = E::ts('Duplication.');
            $errors['expiredTagId'] = E::ts('Duplication.');
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * It rejects the duplications on the custom fields.
     * One custom field has to point to one setting.
     *
     * @param $values
     *
     * @return array|true
     */
    public static function customFieldDuplicationNotAllowed($values)
    {
        $errors = [];
        $valueList = [];
        foreach ($values as $k => $v) {
            if (substr($k, 0, 20) === 'map_custom_field_id_') {
                if (array_key_exists($v, $valueList)) {
                    $errors[$valueList[$v]] = E::ts('Duplication.');
                } elseif ($v !== '0') {
                    $valueList[$v] = $k;
                }
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function buildQuickForm(): void
    {
        $this->add('text', 'consentExpirationYears', E::ts('Consent Expiration Years'), [], true);
        $this->add('text', 'consentExpirationTaggingDays', E::ts('Tag Before Expiration Days'), [], true);
        $this->add('select', 'tagId', E::ts('Tag contact'), ['' => E::ts('- select -')] + CRM_Core_BAO_EntityTag::buildOptions('tag_id', 'search', ['entity_table' => 'civicrm_contact']), true);
        $this->add(
            'select',
            'expiredTagId',
            E::ts('Anonymized Tag'),
            ['' => E::ts('- select -')] + CRM_Core_BAO_EntityTag::buildOptions('tag_id', 'search', ['entity_table' => 'civicrm_contact']),
            true
        );
        $this->add('checkbox', 'consentAfterContribution', E::ts('Consent After Contribution'));
        $this->add('text', 'landing_page', E::ts('Landing page for consent renewal'));
        $this->add('text', 'email_contact', E::ts('Email address for manual data management'));
        // select field for the custom-field-map entries.
        // if we have entries in the map, use the entry length
        // of the entries for indexing, otherwise use 0 index.
        $cfMap = [];
        $config = $this->config->get();
        if (array_key_exists('custom-field-map', $config) === false || count($config['custom-field-map']) === 0) {
            $this->add('select', 'map_custom_field_id_0', '', [0 => E::ts('- select -')] + Service::customCheckboxFields());
            $this->add('select', 'map_consent_field_id_0', '', [0 => E::ts('- select -')] + Service::consentFields());
            $this->add('select', 'map_group_id_0', '', [0 => E::ts('- select -')] + CRM_Contact_BAO_GroupContact::buildOptions('group_id', 'search'));
            $cfMap['map_custom_field_id_0'] = ['consent' => 'map_consent_field_id_0', 'group' => 'map_group_id_0'];
        } else {
            foreach ($config['custom-field-map'] as $k => $v) {
                $this->add('select', 'map_custom_field_id_'.$k, '', [0 => E::ts('- select -')] + Service::customCheckboxFields());
                $this->add('select', 'map_consent_field_id_'.$k, '', [0 => E::ts('- select -')] + Service::consentFields());
                $this->add('select', 'map_group_id_'.$k, '', [0 => E::ts('- select -')] + CRM_Contact_BAO_GroupContact::buildOptions('group_id', 'search'));
                $cfMap['map_custom_field_id_'.$k] = ['consent' => 'map_consent_field_id_'.$k, 'group' => 'map_group_id_'.$k];
            }
        }
        $this->assign('cfMap', $cfMap);

        // Submit button
        $this->addButtons(
            [
                [
                    'type' => 'done',
                    'name' => E::ts('Save'),
                    'isDefault' => true,
                ],
            ]
        );
        $this->setTitle(E::ts('Consentactivity Settings'));
        // js file that handles the new map entry event.
        Civi::resources()->addScriptFile(E::LONG_NAME, 'js/settings.js');
    }

    /**
     * @return void
     * @throws \CRM_Core_Exception
     */
    public function postProcess(): void
    {
        $config = $this->config->get();
        $config['tag-id'] = $this->_submitValues['tagId'];
        $config['expired-tag-id'] = $this->_submitValues['expiredTagId'];
        $config['consent-after-contribution'] = $this->_submitValues['consentAfterContribution'] == '' ? false : true;
        $config['consent-expiration-years'] = $this->_submitValues['consentExpirationYears'];
        $config['consent-expiration-tagging-days'] = $this->_submitValues['consentExpirationTaggingDays'];
        $config['landing-page'] = $this->_submitValues['landing_page'];
        $config['email-contact'] = $this->_submitValues['email_contact'];
        $activityType = Service::getActivityType($config['option-value-id']);
        if ($config['saved-search-id'] === Config::DEFAULT_EXPIRATION_SEARCH_ID) {
            // create it
            $savedSearch = Service::savedSearchExpired($activityType['name'], $config['tag-id'], $config['expired-tag-id']);
            $config['saved-search-id'] = $savedSearch['id'];
        } else {
            Service::savedSearchExpiredUpdate($activityType['name'], $config['tag-id'], $config['expired-tag-id'], $config['saved-search-id']);
        }
        if ($config['tagging-search-id'] === Config::DEFAULT_TAG_SEARCH_ID) {
            // create it
            $savedSearch = Service::savedSearchTagging($activityType['name'], $config['expired-tag-id']);
            $config['tagging-search-id'] = $savedSearch['id'];
        } else {
            Service::savedSearchTaggingUpdate($activityType['name'], $config['expired-tag-id'], $config['tagging-search-id']);
        }
        $customFieldMap = [];
        foreach ($this->_submitValues as $k => $v) {
            if (substr($k, 0, 4) === 'map_') {
                $keyWithId = substr($k, 4);
                $segments = explode('_', $keyWithId);
                // the last segment is the identifier
                $id = $segments[count($segments) - 1];
                // unset the identifier
                unset($segments[count($segments) - 1]);
                $key = implode('-', $segments);
                if (!isset($customFieldMap[$id])) {
                    $customFieldMap[$id] = [];
                }
                $customFieldMap[$id][$key] = $v;
            }
        }
        // filter out the empty data
        $filteredMap = [];
        foreach ($customFieldMap as $value) {
            if ($value['custom-field-id'] !== '0' && $value['consent-field-id'] !== '0') {
                $filteredMap[] = $value;
            }
        }
        $config['custom-field-map'] = $filteredMap;
        if (!$this->config->update($config)) {
            CRM_Core_Session::setStatus(E::ts('Error during search update'), 'Consentactivity', 'error');
        } else {
            CRM_Core_Session::setStatus(E::ts('The configuration has been updated.'), 'Consentactivity', 'success', ['expires' => 5000]);
        }
        // Redirect to the form after the submit.
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/consent-activity', 'reset=1'));
    }
}
