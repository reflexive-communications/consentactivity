<?php

use CRM_Consentactivity_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Consentactivity_Upgrader extends CRM_Extension_Upgrader_Base
{
    /**
     * Install process. Init database.
     *
     * @throws CRM_Core_Exception
     */
    public function install(): void
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        // Create default configs
        if (!$config->create()) {
            throw new CRM_Core_Exception(E::LONG_NAME.E::ts(' could not create configs in database'));
        }
    }

    /**
     * When the extension is enabled, we have to make sure that the setup is still valid.
     * If the activity type has been changed manually, it has to be changed back to the
     * default values. If the activity type is missing, it has to be created again.
     *
     * @throws \CRM_Core_Exception
     */
    public function enable(): void
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $current = CRM_Consentactivity_Service::getActivityType($cfg['option-value-id']);
        if (empty($current)) {
            // missing type, it could be deleted. A new one has to be created.
            $current = CRM_Consentactivity_Service::createDefaultActivityType();
        }
        CRM_Consentactivity_Service::updateExistingActivityType($current['id']);
        if (array_key_exists('tag-id', $cfg) && array_key_exists('expired-tag-id', $cfg)) {
            // If the stored tag id is connected to a deleted tag, set the default value.
            if ($cfg['tag-id'] !== CRM_Consentactivity_Config::DEFAULT_TAG_ID && !CRM_Consentactivity_Service::tagExists(intval($cfg['tag-id']))) {
                $cfg['tag-id'] = CRM_Consentactivity_Config::DEFAULT_TAG_ID;
            }
            if ($cfg['expired-tag-id'] !== CRM_Consentactivity_Config::DEFAULT_EXPIRED_TAG_ID && !CRM_Consentactivity_Service::tagExists(intval($cfg['expired-tag-id']))) {
                $cfg['expired-tag-id'] = CRM_Consentactivity_Config::DEFAULT_EXPIRED_TAG_ID;
            }
            // At this point, if the tag id is default value, delete the saved searches without thinking.
            if ($cfg['tag-id'] === CRM_Consentactivity_Config::DEFAULT_TAG_ID || $cfg['expired-tag-id'] === CRM_Consentactivity_Config::DEFAULT_EXPIRED_TAG_ID) {
                if (array_key_exists('saved-search-id', $cfg) && $cfg['saved-search-id'] !== CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID) {
                    CRM_Consentactivity_Service::deleteSavedSearch($cfg['saved-search-id']);
                    $cfg['saved-search-id'] = CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID;
                }
                if (array_key_exists('tagging-search-id', $cfg) && $cfg['tagging-search-id'] !== CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID) {
                    CRM_Consentactivity_Service::deleteSavedSearch($cfg['tagging-search-id']);
                    $cfg['tagging-search-id'] = CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID;
                }
            } else {
                // validate the search ids. Rebuild them based on the current settings..
                if (array_key_exists('saved-search-id', $cfg) && $cfg['saved-search-id'] !== CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID) {
                    // check that the saved search exists
                    $currentSearch = CRM_Consentactivity_Service::getSavedSearch($cfg['saved-search-id']);
                    if (empty($currentSearch)) {
                        $savedSearch = CRM_Consentactivity_Service::savedSearchExpired($current['name'], $cfg['tag-id'], $cfg['expired-tag-id'], false);
                        $cfg['saved-search-id'] = $savedSearch['id'];
                    }
                }
                if (array_key_exists('tagging-search-id', $cfg) && $cfg['tagging-search-id'] !== CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID) {
                    // check that the saved search exists
                    $currentSearch = CRM_Consentactivity_Service::getSavedSearch($cfg['tagging-search-id']);
                    if (empty($currentSearch)) {
                        $savedSearch = CRM_Consentactivity_Service::savedSearchTagging($current['name'], $cfg['expired-tag-id'], false);
                        $cfg['tagging-search-id'] = $savedSearch['id'];
                    }
                }
            }
        }
        // The consent-field-map validation. the consent field, and the group has to be checked.
        // On case of missing ones, delete the map entry.
        $cfg['activity-type-id'] = $current['value'];
        $cfg['option-value-id'] = $current['id'];
        $config->update($cfg);
    }

    /**
     * Uninstall process. Clean database.
     *
     * @throws CRM_Core_Exception
     */
    public function uninstall(): void
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        // delete current configs
        if (!$config->remove()) {
            throw new CRM_Core_Exception(E::LONG_NAME.E::ts(' could not remove configs from database'));
        }
    }

    /**
     * This update logic has been changed with 5100, as the service API made
     * breaking changes.
     *
     * @return true on success
     * @throws Exception
     */
    public function upgrade_5000(): bool
    {
        return true;
    }

    /**
     * Upgrader function, that inserts the tagging and searching parameters
     * and removes the current saved search, as the content is deprecated.
     *
     * @return true on success
     * @throws Exception
     */
    public function upgrade_5100(): bool
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        if (!array_key_exists('tag-id', $cfg)) {
            $cfg['tag-id'] = CRM_Consentactivity_Config::DEFAULT_TAG_ID;
        }
        if (!array_key_exists('consent-expiration-years', $cfg)) {
            $cfg['consent-expiration-years'] = CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_YEAR;
        }
        if (!array_key_exists('consent-expiration-tagging-days', $cfg)) {
            $cfg['consent-expiration-tagging-days'] = CRM_Consentactivity_Config::DEFAULT_CONSENT_EXPIRATION_TAGGING_DAYS;
        }
        if (!array_key_exists('tagging-search-id', $cfg)) {
            $cfg['tagging-search-id'] = CRM_Consentactivity_Config::DEFAULT_TAG_SEARCH_ID;
        }
        // Deattach old search, as the new version has to use the tagging feature.
        if (!array_key_exists('saved-search-id', $cfg) || $cfg['saved-search-id'] != CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID) {
            CRM_Consentactivity_Service::deleteSavedSearch($cfg['saved-search-id']);
            $cfg['saved-search-id'] = CRM_Consentactivity_Config::DEFAULT_EXPIRATION_SEARCH_ID;
        }
        $config->update($cfg);

        return true;
    }

    /**
     * Upgrader function, that inserts the custom-field-map key.
     *
     * @return true on success
     * @throws Exception
     */
    public function upgrade_5101(): bool
    {
        $config = new CRM_Consentactivity_Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        if (!array_key_exists('custom-field-map', $cfg)) {
            $cfg['custom-field-map'] = CRM_Consentactivity_Config::DEFAULT_CUSTOM_FIELD_MAP;
        }
        if (!array_key_exists('expired-tag-id', $cfg)) {
            $cfg['expired-tag-id'] = CRM_Consentactivity_Config::DEFAULT_EXPIRED_TAG_ID;
        }
        if (!array_key_exists('consent-after-contribution', $cfg)) {
            $cfg['consent-after-contribution'] = false;
        }
        $config->update($cfg);

        return true;
    }
}
