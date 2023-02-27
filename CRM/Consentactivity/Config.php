<?php

class CRM_Consentactivity_Config extends CRM_RcBase_Config
{
    const DEFAULT_CONSENT_EXPIRATION_YEAR = 3;

    const DEFAULT_CONSENT_EXPIRATION_TAGGING_DAYS = 30;

    const DEFAULT_TAG_ID = '0';

    const DEFAULT_EXPIRED_TAG_ID = '0';

    const DEFAULT_EXPIRATION_SEARCH_ID = '0';

    const DEFAULT_TAG_SEARCH_ID = '0';

    const DEFAULT_CUSTOM_FIELD_MAP = [];

    const DEFAULT_LANDING_PAGE = '';

    /**
     * Provides a default configuration object.
     * The activity-type defaults to 0, as it is an invalid activity
     * type id.
     *
     * @return array the default configuration object.
     */
    public function defaultConfiguration(): array
    {
        return [
            'activity-type-id' => 0,
            'option-value-id' => 0,
            // The search for the expiration
            'saved-search-id' => self::DEFAULT_EXPIRATION_SEARCH_ID,
            // The search for the tagging
            'tagging-search-id' => self::DEFAULT_TAG_SEARCH_ID,
            // The tag id that has to be added to the contact
            'tag-id' => self::DEFAULT_TAG_ID,
            // The tag that contains the anonymized contacts.
            'expired-tag-id' => self::DEFAULT_EXPIRED_TAG_ID,
            // Consent activity after contribution.
            'consent-after-contribution' => false,
            // The number of years after the consent gets expired
            // By default it is 3 years
            'consent-expiration-years' => self::DEFAULT_CONSENT_EXPIRATION_YEAR,
            // The number of days before the expiration
            // The tag has to be added at this time.
            'consent-expiration-tagging-days' => self::DEFAULT_CONSENT_EXPIRATION_TAGGING_DAYS,
            // The custom field -> [ privacy_field, group] map.
            'custom-field-map' => self::DEFAULT_CUSTOM_FIELD_MAP,
            // Custom landing page, redirect here after consent renewal
            'landing-page' => self::DEFAULT_LANDING_PAGE,
        ];
    }
}
