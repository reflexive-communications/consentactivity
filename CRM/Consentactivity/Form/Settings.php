<?php

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
     * @var CRM_Consentactivity_Config
     */
    private $config;

    /**
     * Preprocess form
     *
     * @throws CRM_Core_Exception
     */
    public function preProcess()
    {
        // Get current settings
        $this->config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $this->config->load();
    }

    /**
     * Set default values
     *
     * @return array
     */
    public function setDefaultValues()
    {
        $config = $this->config->get();
        // Set defaults
        $this->_defaults['tagId'] = $config['tag-id'];
        $this->_defaults['consentExpirationYears'] = $config['consent-expiration-years'];
        $this->_defaults['consentExpirationTaggingDays'] = $config['consent-expiration-tagging-days'];

        return $this->_defaults;
    }

    /**
     * Register validation rules
     * The import limit has to be numeric value. Client + server side validation.
     */
    public function addRules()
    {
        $this->addRule('consentExpirationYears', ts('Expiration year has to be numeric.'), 'numeric', null, 'client');
        $this->addRule('consentExpirationYears', ts('Expiration year has to be numeric.'), 'numeric');
        $this->addRule('consentExpirationTaggingDays', ts('Tagging days has to be numeric.'), 'numeric', null, 'client');
        $this->addRule('consentExpirationTaggingDays', ts('Tagging days has to be numeric.'), 'numeric');
        $this->addFormRule(['CRM_Consentactivity_Form_Settings', 'zeroNotAllowed']);
    }

    /**
     * Here's our custom validation callback for rejecting
     * the 0 as value for the years or days.
     */
    public static function zeroNotAllowed($values)
    {
        $errors = [];
        if ($values['consentExpirationYears'] === '0') {
            $errors['consentExpirationYears'] = ts('Not allowed value.');
        }
        if ($values['consentExpirationTaggingDays'] === '0') {
            $errors['consentExpirationTaggingDays'] = ts('Not allowed value.');
        }
        return empty($errors) ? true : $errors;
    }

    public function buildQuickForm()
    {
        $this->add('text', 'consentExpirationYears', ts('Consent Expiration Years'), [], true);
        $this->add('text', 'consentExpirationTaggingDays', ts('Tag Before Expiration Days'), [], true);
        $this->add('select', 'tagId', ts('Tag contact'), ['' => ts('- select -')] + CRM_Core_BAO_EntityTag::buildOptions('tag_id', 'search', ['entity_table' => 'civicrm_contact']), true);

        // Submit button
        $this->addButtons(
            [
                [
                    'type' => 'done',
                    'name' => ts('Save'),
                    'isDefault' => true,
                ],
            ]
        );
        $this->setTitle(ts('Consentactivity Settings'));
    }

    public function postProcess()
    {
        $config = $this->config->get();
        $config['tag-id'] = $this->_submitValues['tagId'];
        $config['consent-expiration-years'] = $this->_submitValues['consentExpirationYears'];
        $config['consent-expiration-tagging-days'] = $this->_submitValues['consentExpirationTaggingDays'];
        $activityType = CRM_Consentactivity_Service::getActivityType($config['option-value-id']);
        if ($config['saved-search-id'] === CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID) {
            // create it
            $savedSearch = CRM_Consentactivity_Service::savedSearchExpired($activityType['name'], $config['tag-id']);
            $config['saved-search-id'] = $savedSearch['id'];
        } else {
            CRM_Consentactivity_Service::savedSearchExpiredUpdate($activityType['name'], $config['tag-id'], $config['saved-search-id']);
        }
        if ($config['tagging-search-id'] === CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID) {
            // create it
            $savedSearch = CRM_Consentactivity_Service::savedSearchTagging($activityType['name'], $config['tag-id']);
            $config['tagging-search-id'] = $savedSearch['id'];
        } else {
            CRM_Consentactivity_Service::savedSearchTaggingUpdate($activityType['name'], $config['tag-id'], $config['tagging-search-id']);
        }
        if (!$this->config->update($config)) {
            CRM_Core_Session::setStatus(ts('Error during search update'), 'Consentactivity', 'error');
        } else {
            CRM_Core_Session::setStatus(ts('The configuration has been updated.'), 'Consentactivity', 'success', ['expires' => 5000,]);
        }
        // Redirect to the form after the submit.
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/consent-activity', 'reset=1'));
    }
}
