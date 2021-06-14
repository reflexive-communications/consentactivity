# consentactivity

This extension could be used for tracking GDPR related activities. It supports the following use case:
- The contact data could be used for N years after the consent.
- The consent is defined as a form submission that contains at least one of the following contact parameters: `do_not_mail`, `do_not_phone`, `is_opt_out`

An activity is added to the contact when it fills a form that contains the consent parameters. The date of the activity is the date of the consent. The status of the activity will be completed.
The activity type is created by the extension. If an existing activity type has to be used, you can make it happen if you change the label of the type to the extension default value, that is `GDPR Consent Activity`.

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

When the extension is installed, it creates the default setting. During the postInstall step, it searches for an activity type that has a given label. If not found, then it creates it in this step. Finally it updates the default setting values with the option group id and the value of this type. The option group id is mapped to `option-value-id` and the value is to `activity-type-id`.

When the extension is enabled, it validates the setting values. In case of data corruption it creates the activity type again and updates the setting with the new values.

When the extension is uninstalled, it deletes the settings. The activity type and the activities are not changed during the uninstall process.
