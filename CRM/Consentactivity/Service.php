<?php

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Activity;
use CRM_Consentactivity_ExtensionUtil as E;

class CRM_Consentactivity_Service
{
    public const DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL = 'GDPR Consent Activity';
    public const DEFAULT_CONSENT_ACTIVITY_TYPE_ICON = 'fa-thumbs-o-up';
    public const FORMS_THAT_COULD_CONTAIN_OPT_OUT_FIELDS = [
        'CRM_Campaign_Form_Petition_Signature',
        'CRM_Profile_Form_Edit',
    ];
    public const CONSENT_FIELDS = [
        'do_not_email',
        'do_not_phone',
        'is_opt_out',
    ];
    /*
     * It creates the activity type for the gdpr consent activity.
     * By default it usess the hardcoded values. If an existing activity has to be used as
     * default consent activity, the label has to be updated to the default value. The service
     * will use that one.
     *
     * @return array
     */
    public static function createDefaultActivityType(): array
    {
        $activityTypeOptionGroupId = self::getActivityTypeOptionGroupId();
        $currentActivityType = self::findActivityType($activityTypeOptionGroupId);
        if (count($currentActivityType) > 0) {
            return $currentActivityType;
        }
        $result = OptionValue::create(false)
            ->addValue('option_group_id', $activityTypeOptionGroupId)
            ->addValue('label', self::DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL)
            ->addValue('is_active', true)
            ->addValue('is_reserved', true)
            ->addValue('icon', self::DEFAULT_CONSENT_ACTIVITY_TYPE_ICON)
            ->execute()
            ->first();
        return $result;
    }
    /*
     * It updates an existing activity type with making it reserved and active.
     *
     * @param int $optionValueId
     *
     * @return array
     */
    public static function updateExistingActivityType(int $optionValueId): array
    {
        $result = OptionValue::update(false)
            ->addWhere('id', '=', $optionValueId)
            ->addValue('is_active', true)
            ->addValue('is_reserved', true)
            ->execute()
            ->first();
        return $result;
    }
    /**
     * This function is responsible for handling the email, phone opt-out values.
     * In case of at least one parameter is present on the target form, it triggers
     * a consent activity.
     *
     * @param string $formName the name of the current form
     * @param CRM_Core_Form $form
     */
    public static function postProcess(string $formName, $form)
    {
        if (!self::formNameIsInFormList($formName)) {
            return;
        }
        if (!self::hasConsentFieldOnTheForm($form->getVar('_submitValues'))) {
            return;
        }
        // on the petition form, the contact id is saved as contactID. on the profiles it is id.
        $cid = $formName === 'CRM_Campaign_Form_Petition_Signature' ? $form->getVar('_contactId') : $form->getVar('_id');
        self::createConsentActivityToContact($cid);
    }
    /**
     * This function is responsible for creating the consent activity for the given
     * contact. The visibility of this function is public, so that it could be called
     * if the contact update is happening with an API action.
     *
     * @param int $contactId the id of the contact that triggers the activity
     */
    public static function createConsentActivityToContact(int $contactId)
    {
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $results = Activity::create(false)
            ->addValue('activity_type_id', $config['activity-type-id'])
            ->addValue('source_contact_id', $contactId)
            ->addValue('status_id:name', 'Completed')
            ->execute();
        // on case of invalid result (missing id field), it prints error to the log
        if (count($results) !== 1 || !array_key_exists('id', $results[0])) {
            Civi::log()->error('Consentactivity | Failed to create Activity for the following contact: '.$contactId);
        }
    }
    /*
     * It is a wrapper function for option value get api call.
     *
     * @param int $optionValueId
     *
     * @return array
     */
    public static function getActivityType(int $optionValueId): array
    {
        $result = OptionValue::get(false)
            ->addWhere('id', '=', $optionValueId)
            ->execute()
            ->first();
        return $result ?? [];
    }
    /*
     * It returns true if the given form contains field that connected
     * to the consents.
     *
     * @param array $submittedValues the submitted values
     *
     * @return bool
     */
    private static function hasConsentFieldOnTheForm(array $submittedValues): bool
    {
        foreach (self::CONSENT_FIELDS as $f) {
            if (array_key_exists($f, $submittedValues)) {
                return true;
            }
        }
        return false;
    }
    /*
     * It returns true if the given formName is in the predefined list.
     * Otherwise it returns false.
     *
     * @param string $formName the name of the current form
     *
     * @return bool
     */
    private static function formNameIsInFormList(string $formName): bool
    {
        return array_search($formName, self::FORMS_THAT_COULD_CONTAIN_OPT_OUT_FIELDS) > -1;
    }
    /*
     * This function gets the option group id of the activity_type option group.
     * It will be necessary for finding the option value.
     *
     * @return int
     */
    private static function getActivityTypeOptionGroupId(): int
    {
        $optionGroup = OptionGroup::get(false)
            ->addSelect('id')
            ->addWhere('name', '=', 'activity_type')
            ->setLimit(1)
            ->execute()
            ->first();
        return $optionGroup['id'];
    }
    /*
     * It returns an array as activity type.
     * It tries to find the existing activity type. If not found
     * It returns empty array.
     *
     * @param int $optionGroupId
     *
     * @return array
     */
    private static function findActivityType(int $optionGroupId): array
    {
        $optionValues = OptionValue::get(false)
            ->addWhere('option_group_id', '=', $optionGroupId)
            ->addWhere('label', '=', self::DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL)
            ->setLimit(1)
            ->execute();
        if (count($optionValues) === 0) {
            return [];
        }
        $optionValue = $optionValues->first();
        if ($optionValue['is_active'] && $optionValue['is_reserved']) {
            return $optionValue;
        }
        // Set it active to be able to use it later.
        return self::updateExistingActivityType($optionValue['id']);
    }
}
