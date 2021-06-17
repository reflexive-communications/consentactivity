<?php

class CRM_Consentactivity_Config extends CRM_RcBase_Config
{
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
            'saved-search-id' => 0,
        ];
    }
}
