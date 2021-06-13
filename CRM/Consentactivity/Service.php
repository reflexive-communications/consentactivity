<?php

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

class CRM_Consentactivity_Service
{
    const DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL = 'GDPR Consent Activity';
    /*
     * It creates the activity type for the gdpr consent activity.
     * By default it usess the hardcoded values. If an existing activity has to be used as
     * default consent activity, the label has to be updated to the default value. The service
     * will use that one.
     *
     * @return int
     */
    public static function createDefaultActivityType(): int
    {
        $activityTypeOptionGroupId = self::getActivityTypeOptionGroupId();
        $currentActivityTypeId = self::getActivityTypeId($activityTypeOptionGroupId);
        if ($currentActivityTypeId > 0) {
            return $currentActivityTypeId;
        }
        $result = OptionValue::create()
            ->addValue('option_group_id', $activityTypeOptionGroupId)
            ->addValue('label', self::DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL)
            ->addValue('is_active', true)
            ->addValue('is_reserved', true)
            ->addValue('icon', 'fa-thumbs-o-up')
            ->execute()
            ->first();
        return $result['id'];
    }
    /*
     * It updates an existing activity type with making it reserved and active.
     *
     * @param int $optionValueId
     *
     * @return int
     */
    public static function updateExistingActivityType(int $optionValueId): int
    {
        OptionValue::update()
            ->addWhere('id', '=', $optionValueId)
            ->addValue('is_active', true)
            ->addValue('is_reserved', true)
            ->execute();
        return $optionValueId;
    }
    /*
     * This function gets the option group id of the activity_type option group.
     * It will be necessary for finding the option value.
     *
     * @return int
     */
    private static function getActivityTypeOptionGroupId(): int
    {
        $optionGroup = OptionGroup::get()
            ->addSelect('id')
            ->addWhere('name', '=', 'activity_type')
            ->setLimit(1)
            ->execute()
            ->first();
        return $optionGroup['id'];
    }
    /*
     * It returns a not negative integer value as activity type id.
     * It tries to find the id of the existing activity type. If not found
     * It returns 0.
     *
     * @param int $optionGroupId
     *
     * @return int
     */
    private static function getActivityTypeId(int $optionGroupId): int
    {
        $optionValues = OptionValue::get()
            ->addSelect('id', 'is_active')
            ->addWhere('option_group_id', '=', $optionGroupId)
            ->addWhere('label', '=', self::DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL)
            ->setLimit(1)
            ->execute();
        if (count($optionValues) === 0) {
            return 0;
        }
        $optionValue = $optionValues->first();
        if ($optionValue['is_active']) {
            return $optionValue['id'];
        }
        // Set it active to be able to use it later.
        return self::updateExistingActivityType($optionValue['id']);
    }
}
