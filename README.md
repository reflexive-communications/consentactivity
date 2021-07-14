# consentactivity

This extension could be used for tracking GDPR related activities. It supports the following use case:
- The contact data could be used for N years after the consent.
- The consent is defined as a form submission that contains at least one of the following contact parameters: `do_not_mail`, `do_not_phone`, `is_opt_out`

An activity is added to the contact when it fills a form that contains the consent parameters. The date of the activity is the date of the consent. The status of the activity will be completed.
The activity type is created by the extension. If an existing activity type has to be used, you can make it happen if you change the label of the type to the extension default value, that is `GDPR Consent Activity`.

The search-kit extension could be used for finding the contacts where the last consent activity was more than N years ago. This extension provides a saved search that contains the basic setup. The date field has to be updated in the Having statement.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.3+
* CiviCRM v5.37.1
* RcBase v0.8.2

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone git@github.com:reflexive-communications/consentactivity.git
cv en consentactivity
```

### Upgrader

When the extension is installed, it creates the default setting.

On the enable step it validates the settings that is created before. It validates the activity type. If activity type is not found it creates one and makes it active and reserved. If the config contains the tag-id key, it is also validated. If the given tag is missing, the tag and the search ids are reseted to initial values. If the tag id is valid, the search ids are needs to be checked. If the searches with the given ids are deleted (not found), new ones are created. After the validation the setting is updated with the valid values.

When the extension is uninstalled, it deletes the settings. The activity type and the activities are not changed during the uninstall process.

When the upgrade-db task is running, it checks for the existance of the setting keys. They are set with default values. The saved-search is deleted as the tag id dependency can not be handled.

### Stored configuration

The extension has in internal setting database where the following parameters are stored:

- `activity-type-id`
- `option-value-id`
- `saved-search-id` The search for the expiration.
- `tagging-search-id` The search for the tagging.
- `tag-id` The tag id that has to be added to the contact.
- `consent-expiration-years` The number of years after the consent gets expired. By default it is 3 years.
- `consent-expiration-tagging-days` The number of days before the expiration. The tag has to be added at this time.
