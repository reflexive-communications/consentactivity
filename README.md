# consentactivity

[![CI](https://github.com/reflexive-communications/consentactivity/actions/workflows/main.yml/badge.svg)](https://github.com/reflexive-communications/consentactivity/actions/workflows/main.yml)

**THIS REPO HAS BEEN ARCHIVED!**

This extension could be used for tracking GDPR related activities.
A contact gives consent when:

- submits a profile, a petition or event form.
- creates a contribution from a form of with import (if the contribution is created in the last N years, and the feature is enabled).
- clicks to the link that is evaluated from the `Consentactivity.consent_renewal` email token.

An activity is added to the contact when the consent is given.
A search kit saved search is supplied to search for contacts with expired & nearly expired consents. The date field has to be updated in the Having statement.
The `{Consentactivity.consent_renewal}` token could be used to create a link that leads the contact to a page that adds the consent activity to the contact in the background.

The consent expiration is managed with scheduled jobs. Before the expiration a tag is added to the contact.
This action could be used for triggering further actions (eg: sending emails).
After the expiration the contact is anonymized with a data deletion process and the expired tag is also added to contact.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

- PHP v7.3+
- CiviCRM v5.76+
- rc-base

## Installation

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone git@github.com:reflexive-communications/consentactivity.git
cv en consentactivity
```

## Getting Started

The settings form could be reached from the **Administer > Consentactivity Settings** menu.
You can set the expiration parameters, enable or disable the consent activity after contribution and define the pseudo privacy fields.
For details check the [Developer Notes](DEVELOPER.md).
