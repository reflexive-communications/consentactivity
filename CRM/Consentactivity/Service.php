<?php

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Activity;
use Civi\Api4\SavedSearch;
use Civi\Api4\Tag;
use Civi\Api4\EntityTag;
use Civi\Api4\Contact;
use Civi\Api4\GroupContact;
use Civi\Api4\Email;
use Civi\Api4\Address;
use Civi\Api4\IM;
use Civi\Api4\Phone;
use Civi\Api4\Website;
use CRM_Consentactivity_ExtensionUtil as E;

class CRM_Consentactivity_Service
{
    public const DEFAULT_CONSENT_ACTIVITY_TYPE_LABEL = 'GDPR Consent Activity';
    public const DEFAULT_CONSENT_ACTIVITY_TYPE_ICON = 'fa-thumbs-o-up';
    public const FORMS_THAT_COULD_CONTAIN_OPT_OUT_FIELDS = [
        'CRM_Campaign_Form_Petition_Signature',
        'CRM_Profile_Form_Edit',
        'CRM_Event_Form_Registration_Register',
        'CRM_Event_Form_Registration_Confirm',
    ];
    public const EXPIRED_SEARCH_LABEL = 'Contacts with expired consents';
    public const TAGGING_SEARCH_LABEL = 'Contacts with nearly expired consents for tagging';
    public const CONSENT_FIELDS = [
        'do_not_email',
        'do_not_phone',
        'is_opt_out',
    ];
    public const CONTACT_DATA_ENTITIES = [
        '\Civi\Api4\Website',
        '\Civi\Api4\IM',
        '\Civi\Api4\Phone',
        '\Civi\Api4\Address',
        '\Civi\Api4\Email',
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
     * As the update does not return all fields, the getActivityType function is
     * returned.
     *
     * @param int $optionValueId
     *
     * @return array
     */
    public static function updateExistingActivityType(int $optionValueId): array
    {
        OptionValue::update(false)
            ->addWhere('id', '=', $optionValueId)
            ->addValue('is_active', true)
            ->addValue('is_reserved', true)
            ->execute();
        return self::getActivityType($optionValueId);
    }
    /**
     * This function is responsible for triggering a consent activity on case a petition
     * profile or event form has been submitted.
     *
     * @param string $formName the name of the current form
     * @param CRM_Core_Form $form
     */
    public static function postProcess(string $formName, $form)
    {
        if (!self::formNameIsInFormList($formName)) {
            return;
        }
        // on the petition form, the contact id is saved as contactID. on the profiles it is id.
        $cid = $formName === 'CRM_Campaign_Form_Petition_Signature' ? $form->getVar('_contactId') : $form->getVar('_id');
        // when the form name is event registration register and the event registration process
        // also contains confirm screen, we can return, as it will be handled on that screen
        // without the confirm screen, we have to process it now.
        // On the event forms the contact id could be found under the participant.
        if ($formName === 'CRM_Event_Form_Registration_Register') {
            $values = $form->getVar('_values');
            if ($values['event']['is_confirm_enabled']) {
                return;
            }
            $cid = $values['participant']['contact_id'];
        } elseif ($formName === 'CRM_Event_Form_Registration_Confirm') {
            $values = $form->getVar('_values');
            $cid = $values['participant']['contact_id'];
        }
        self::createConsentActivityToContact($cid);
        // handle the consent field and group insertion
        self::consentFieldAndGroupMaintenace($cid, $form->getVar('_submitValues'));
    }
    /**
     * This function is responsible for creating the consent activity for the given
     * contact. The visibility of this function is public, so that it could be called
     * if the contact update is happening with an API action.
     *
     * @param int $contactId the id of the contact that triggers the activity
     *
     * @return array the created activity.
     */
    public static function createConsentActivityToContact(int $contactId): array
    {
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $activity = Activity::create(false)
            ->addValue('activity_type_id', $config['activity-type-id'])
            ->addValue('source_contact_id', $contactId)
            ->addValue('target_contact_id', $contactId)
            ->addValue('status_id:name', 'Completed')
            ->execute()
            ->first();
        // on case of invalid result, it prints error to the log
        if (is_null($activity)) {
            Civi::log()->error('Consentactivity | Failed to create Activity for the following contact: '.$contactId);
            $activity = [];
        }
        // Remove the expired tag from the contact if the tag-id is set in the config.
        // The result checking is skipped, because not every contact has this tag, only the old ones.
        if ($config['tag-id'] !== CRM_Consentactivity_Config::DEFAULT_TAG_ID) {
            EntityTag::delete(false)
                ->addWhere('entity_table', '=', 'civicrm_contact')
                ->addWhere('entity_id', '=', $contactId)
                ->addWhere('tag_id', '=', $config['tag-id'])
                ->execute();
        }
        return $activity;
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
     * This function creates a saved search, that could be the base query of the
     * gathering process of the contacts with old consents.
     *
     * @param string $activityName
     *
     * @return array
     */
    public static function savedSearchExpired(string $activityName, string $tagId, string $anonimizedTagId, bool $aclFlag = true): array
    {
        $results = SavedSearch::create($aclFlag)
            ->addValue('label', self::EXPIRED_SEARCH_LABEL)
            ->addValue('api_entity', 'Contact')
            ->addValue('api_params', self::savedSearchExpiredApiParams($activityName, $tagId, $anonimizedTagId))
            ->execute();
        return $results->first();
    }
    public static function savedSearchExpiredUpdate(string $activityName, string $tagId, string $anonimizedTagId, int $savedSearchId, bool $aclFlag = true): array
    {
        $results = SavedSearch::update($aclFlag)
            ->addWhere('id', '=', $savedSearchId)
            ->addValue('label', self::EXPIRED_SEARCH_LABEL)
            ->addValue('api_entity', 'Contact')
            ->addValue('api_params', self::savedSearchExpiredApiParams($activityName, $tagId, $anonimizedTagId))
            ->execute();
        return $results->first();
    }
    /*
     * This function creates a saved search, that could be the base query of the
     * gathering process of the contacts with old consents.
     *
     * @param string $activityName
     *
     * @return array
     */
    public static function savedSearchTagging(string $activityName, string $tagId, bool $aclFlag = true): array
    {
        $results = SavedSearch::create($aclFlag)
            ->addValue('label', self::TAGGING_SEARCH_LABEL)
            ->addValue('api_entity', 'Contact')
            ->addValue('api_params', self::savedSearchTaggingApiParams($activityName, $tagId))
            ->execute();
        return $results->first();
    }
    public static function savedSearchTaggingUpdate(string $activityName, string $tagId, int $savedSearchId, bool $aclFlag = true): array
    {
        $results = SavedSearch::update($aclFlag)
            ->addWhere('id', '=', $savedSearchId)
            ->addValue('label', self::TAGGING_SEARCH_LABEL)
            ->addValue('api_entity', 'Contact')
            ->addValue('api_params', self::savedSearchTaggingApiParams($activityName, $tagId))
            ->execute();
        return $results->first();
    }
    /*
     * It is a wrapper function for saved search get api call.
     *
     * @param int $savedSearchId
     *
     * @return array
     */
    public static function getSavedSearch(int $savedSearchId): array
    {
        $result = SavedSearch::get(false)
            ->addWhere('id', '=', $savedSearchId)
            ->execute()
            ->first();
        return $result ?? [];
    }
    /*
     * It is a wrapper function for saved searchdelete api call.
     *
     * @param int $savedSearchId
     *
     * @return array
     */
    public static function deleteSavedSearch(int $savedSearchId): array
    {
        $result = SavedSearch::delete(false)
            ->addWhere('id', '=', $savedSearchId)
            ->execute()
            ->first();
        return $result ?? [];
    }
    /*
     * It checks that the tag with the given tagId exists
     * or not.
     *
     * @param int $tagId
     *
     * @return bool
     */
    public static function tagExists(int $tagId): bool
    {
        $tags = Tag::get(false)
            ->addWhere('id', '=', $tagId)
            ->setLimit(1)
            ->execute();
        return count($tags) === 1;
    }
    /*
     * It returns the consend field options for the settings
     * admin field.
     *
     * @return array
     */
    public static function consentFields(): array
    {
        $fields = CRM_Core_BAO_UFField::getAvailableFields();
        $paramOptions = [];
        foreach ($fields as $k => $v) {
            if ($k !== 'Contact') {
                continue;
            }
            foreach ($v as $key => $value) {
                // filter the consent fields
                if (array_search($key, self::CONSENT_FIELDS) !== false) {
                    $paramOptions[$key] = $value['title'];
                }
            }
        }
        return $paramOptions;
    }
    /*
     * It returns the custom field options for the settings
     * admin field. Only the checkbox types are returned.
     *
     * @return array
     */
    public static function customCheckboxFields(): array
    {
        $fields = CRM_Core_BAO_UFField::getAvailableFields();
        $contactParamNames = ['Contact', 'Individual'];
        $paramOptions = [];
        foreach ($fields as $k => $v) {
            if (array_search($k, $contactParamNames) === false) {
                continue;
            }
            foreach ($v as $key => $value) {
                if (!array_key_exists('html_type', $value) || $value['html_type'] !== 'CheckBox') {
                    continue;
                }
                if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key)) {
                    $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customFieldId, 'custom_group_id');
                    $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'title');
                    $paramOptions[$key] = $value['title'] . ' :: ' . $customGroupName;
                }
            }
        }
        return $paramOptions;
    }
    /*
     * It deletes the following contact related data:
     * - Webpage
     * - Instant message addresses
     * - Phones
     * - Addresses
     * - Email addresses
     * Deletes the following contact params:
     * - first_name, last_name, middle_name, display_name, email_greeting_display,
     * postal_greeting_display, addressee_display, nick_name, sort_name, external_identifier
     * image_url, api_key, birth_date, deceased_date, employer_id, job_title, gender_id,
     * Also sets the privacy flags.
     *
     * @param int $contactId
     */
    public static function anonymizeContact(int $contactId): void
    {
        foreach (self::CONTACT_DATA_ENTITIES as $entity) {
            $numberOfEntities = $entity::get(false)
                ->addWhere('contact_id', '=', $contactId)
                ->selectRowCount()
                ->execute();
            if (count($numberOfEntities)) {
                $entity::delete(false)
                    ->addWhere('contact_id', '=', $contactId)
                    ->setLimit(count($numberOfEntities))
                    ->execute();
            }
        }
        $contactFieldsToDelete = [
            'first_name', 'last_name', 'middle_name', 'display_name',
            'email_greeting_display', 'postal_greeting_display',
            'addressee_display', 'nick_name', 'sort_name',
            'external_identifier', 'image_url', 'api_key', 'birth_date',
            'deceased_date', 'employer_id', 'job_title', 'gender_id',
        ];
        $privacyFieldsToSet = [
            'do_not_email', 'do_not_phone', 'do_not_mail',
            'do_not_sms', 'do_not_trade', 'is_opt_out',
        ];
        $contactRequest = Contact::update(false)
            ->addWhere('id', '=', $contactId)
            ->setLimit(1);
        foreach ($contactFieldsToDelete as $field) {
            $contactRequest = $contactRequest->addValue($field, '');
        }
        foreach ($privacyFieldsToSet as $field) {
            $contactRequest = $contactRequest->addValue($field, true);
        }
        $contactRequest->execute();
    }
    /**
     * On case of contribution create it adds a consentactivity action
     * to the contributor contact if it is configured on the settings form.
     *
     * @param string $op
     * @param string $objectName
     * @param $objectId - the unique identifier for the object.
     * @param $objectRef - the reference to the object if available.
     */
    public static function post(string $op, string $objectName, $objectId, &$objectRef): void
    {
        if ($op !== 'create' || $objectName !== 'Contribution' || $objectRef->is_test) {
            return;
        }
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        if (!$config['consent-after-contribution']) {
            return;
        }
        $receiveDate = date('Y-m-d H:i', strtotime($objectRef->receive_date));
        $expireDate = date('Y:m-d H:i', strtotime($config['consent-expiration-years'].' years ago'));
        if ($receiveDate < $expireDate) {
            return;
        }
        $activity = self::createConsentActivityToContact($objectRef->contact_id);
        if (isset($activity['id'])) {
            // update activity with sql
            $sql = "UPDATE civicrm_activity SET created_date = %1, activity_date_time = %1 WHERE id =  %2";
            $params = [
                1 => [$receiveDate, 'String'],
                2 => [$activity['id'], 'Int'],
            ];
            CRM_Core_DAO::executeQuery($sql, $params);
        }
    }
    /*
     * It checks the form variables and does the actions based on the
     * settings in the consent admin form.
     *
     * @param int $contactId
     * @param array $submitValues
     */
    private static function consentFieldAndGroupMaintenace(int $contactId, array $submitValues): void
    {
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        foreach ($config['custom-field-map'] as $rule) {
            $customField = $rule['custom-field-id'];
            // custom field is not set on the form
            if (array_key_exists($customField, $submitValues) === false) {
                continue;
            }
            // Expected field contains only one item and not more like the contact consent fields.
            if ($submitValues[$customField][1] !== '') {
                self::giveConsentIfNotYetGiven($contactId, $rule['consent-field-id']);
                $groupId = $rule['group-id'];
                if ($groupId !== '0') {
                    self::addContactToGroup($contactId, $groupId);
                }
            }
        }
    }
    /*
     * It handles the group insertion or status update action.
     *
     * @param int $contactId
     * @param int $groupId
     */
    private static function addContactToGroup(int $contactId, int $groupId): void
    {
        $result = GroupContact::get(false)
            ->addSelect('id', 'status')
            ->addWhere('contact_id', '=', $contactId)
            ->addWhere('group_id', '=', $groupId)
            ->setLimit(1)
            ->execute()
            ->first();
        // already in the group but not added status, update it.
        if (is_array($result)) {
            if ($result['status'] !== 'Added') {
                // update.
                GroupContact::update(false)
                    ->addWhere('id', '=', $result['id'])
                    ->addValue('status', 'Added')
                    ->setLimit(1)
                    ->execute();
            }
            return;
        }
        GroupContact::create(false)
            ->addValue('contact_id', $contactId)
            ->addValue('group_id', $groupId)
            ->addValue('status', 'Added')
            ->execute();
    }
    /*
     * It updates the contact consent field to the given state
     * if it is not yet given.
     *
     * @param int $contactId
     * @param string $consentField
     */
    private static function giveConsentIfNotYetGiven(int $contactId, string $consentField): void
    {
        $contact = Contact::get(false)
            ->addWhere('id', '=', $contactId)
            ->addSelect($consentField)
            ->setLimit(1)
            ->execute()
            ->first();
        if ($contact[$consentField]) {
            Contact::update(false)
                ->addWhere('id', '=', $contactId)
                ->addValue($consentField, '')
                ->setLimit(1)
                ->execute();
        }
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
    private static function savedSearchTaggingApiParams(string $activityName, string $tagId): array
    {
        return [
            'version' => 4,
            'select' => [
                'id',
                'GROUP_CONCAT(Contact_ActivityContact_Activity_01.activity_type_id:label) AS GROUP_CONCAT_Contact_ActivityContact_Activity_01_activity_type_id_label',
                'MAX(Contact_ActivityContact_Activity_01.created_date) AS MAX_Contact_ActivityContact_Activity_01_created_date',
            ],
            'orderBy' => [],
            'where' => [],
            'groupBy' => [
                'id'
            ],
            'join' => [
                [
                    'Activity AS Contact_ActivityContact_Activity_01',
                    'INNER',
                    'ActivityContact',
                    [
                        'id',
                        '=',
                        'Contact_ActivityContact_Activity_01.contact_id'
                    ],
                    [
                        'Contact_ActivityContact_Activity_01.record_type_id:name',
                        '=',
                        '"Activity Targets"'
                    ],
                    [
                        'Contact_ActivityContact_Activity_01.activity_type_id:name',
                        '=',
                        '"'.$activityName.'"'
                    ]
                ],
                [
                    'Tag AS Contact_EntityTag_Tag_01',
                    'EXCLUDE',
                    'EntityTag',
                    [
                        'id',
                        '=',
                        'Contact_EntityTag_Tag_01.entity_id'
                    ],
                    [
                        'Contact_EntityTag_Tag_01.entity_table',
                        '=',
                        '"civicrm_contact"'
                    ],
                    [
                        'Contact_EntityTag_Tag_01.id',
                        '=',
                        '"'.$tagId.'"'
                    ],
                ],
            ],
            'having' => [
                [
                    'MAX_Contact_ActivityContact_Activity_01_created_date',
                    '<',
                    '2021-06-17 11:50'
                ],
            ],
        ];
    }
    private static function savedSearchExpiredApiParams(string $activityName, string $tagId, string $anonimizedTagId): array
    {
        return [
            'version' => 4,
            'select' => [
                'id',
                'GROUP_CONCAT(Contact_ActivityContact_Activity_01.activity_type_id:label) AS GROUP_CONCAT_Contact_ActivityContact_Activity_01_activity_type_id_label',
                'MAX(Contact_ActivityContact_Activity_01.created_date) AS MAX_Contact_ActivityContact_Activity_01_created_date',
            ],
            'orderBy' => [],
            'where' => [],
            'groupBy' => [
                'id'
            ],
            'join' => [
                [
                    'Activity AS Contact_ActivityContact_Activity_01',
                    'INNER',
                    'ActivityContact',
                    [
                        'id',
                        '=',
                        'Contact_ActivityContact_Activity_01.contact_id'
                    ],
                    [
                        'Contact_ActivityContact_Activity_01.record_type_id:name',
                        '=',
                        '"Activity Targets"'
                    ],
                    [
                        'Contact_ActivityContact_Activity_01.activity_type_id:name',
                        '=',
                        '"'.$activityName.'"'
                    ]
                ],
                [
                    'Tag AS Contact_EntityTag_Tag_01',
                    'INNER',
                    'EntityTag',
                    [
                        'id',
                        '=',
                        'Contact_EntityTag_Tag_01.entity_id'
                    ],
                    [
                        'Contact_EntityTag_Tag_01.entity_table',
                        '=',
                        '"civicrm_contact"'
                    ],
                    [
                        'Contact_EntityTag_Tag_01.id',
                        '=',
                        '"'.$tagId.'"'
                    ],
                ],
                [
                    'Tag AS Contact_EntityTag_Tag_02',
                    'EXCLUDE',
                    'EntityTag',
                    [
                        'id',
                        '=',
                        'Contact_EntityTag_Tag_02.entity_id'
                    ],
                    [
                        'Contact_EntityTag_Tag_02.entity_table',
                        '=',
                        '"civicrm_contact"'
                    ],
                    [
                        'Contact_EntityTag_Tag_02.id',
                        '=',
                        '"'.$anonimizedTagId.'"'
                    ],
                ],
            ],
            'having' => [
                [
                    'MAX_Contact_ActivityContact_Activity_01_created_date',
                    '<',
                    '2021-06-17 11:50'
                ],
            ],
        ];
    }
}
