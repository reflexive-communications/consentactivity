# Developer Notes

### Upgrader

When the extension is installed, it creates the default setting.

On the enable step it validates the settings that is created before. It validates the activity type. If activity type is not found it creates one and makes it active and reserved. If the config contains the tag-id key and expired-tag-key, it is also validated. If a given tag is missing, the tag and the search ids are reseted to initial values. If both tag id are valid, the search ids are needs to be checked. If the searches with the given ids are deleted (not found), new ones are created. After the validation the setting is updated with the valid values.

When the extension is uninstalled, it deletes the settings. The activity type and the activities are not changed during the uninstall process.

When the upgrade-db task is running, it checks for the existance of the setting keys. They are set with default values. The saved-search is deleted as the tag id dependency can not be handled.

### Stored configuration

The extension has in internal setting database where the following parameters are stored:

- `activity-type-id`
- `option-value-id`
- `saved-search-id` The search for the expiration.
- `tagging-search-id` The search for the tagging.
- `tag-id` The tag id that has to be added to the contact.
- `expired-tag-id` The tag id that has to be added to the contact after the consent expiration.
- `consent-after-contribution` If this flag is set true, the consent activity will be triggered after the contribution.
- `consent-expiration-years` The number of years after the consent gets expired. By default it is 3 years.
- `consent-expiration-tagging-days` The number of days before the expiration. The tag has to be added at this time.
- `custom-field-map` This array contains the associations between the pseudo consent fields and the actual consent fields and groups.

### Scheduled jobs

The extension provides two API endpoints and daily scheduled processes.

- The tagging job applies a given tag to the contacts that are found by the tagging saved search. The tag is added if the latest consent activity is older than `Now - consent-expiration-years + consent-expiration-tagging-days`.
- The expiration job deletes the contact data, sets the privacy field to not given consent state and adds the expiration-tag to the contact that are found by the expiration saved search. (The latest consent activity is older than `Now - consent-expiration-years`.)

### Deleted saved search

If the saved searches have been accidentally deleted (eg from the search kit), you can recreate them with updating the tag-id on the consentactivity settings screen.
