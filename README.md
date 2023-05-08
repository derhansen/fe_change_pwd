[![Build Status](https://github.com/derhansen/fe_change_pwd/workflows/CI/badge.svg?branch=master)](https://github.com/derhansen/fe_change_pwd/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/derhansen/fe_change_pwd/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/derhansen/fe_change_pwd/?branch=master)
[![Monthly Downloads](https://poser.pugx.org/derhansen/fe_change_pwd/d/monthly)](https://packagist.org/packages/derhansen/fe_change_pwd)
[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)

# Change password for frontend users

## What does it do?

This TYPO3 extension contains a plugin to allow logged in frontend users to change their password. The new user
password is validated against the TYPO3 password policy for frontend users.

Password changes for frontend users can be enforced and passwords can expire after a certain amount of days.

**Features:**

* Change password plugin
* Validates the password against the TYPO3 password policies for frontend users
* Force password change for frontend users
* Redirect to configured page when password change is required (uses PSR-15 Middleware in TYPO3 v9)
* Password expiration after a configurable amount of days
* Optional require the current password in order to change it

## Screenshot

The screenshot below shows the output of the "Change Frontend User Password" plugin after the user tried to submit
a weak password.

![Screenshot of the plugin output](Documentation/Images/plugin-output.png "Output of the plugin after password validation")

## Installation

1) Install the extension from the TYPO3 Extension Repository or using composer and add the Static Typoscript
"Change password for frontend users" to your TypoScript template.

2) Create a new page and make sure, that the page is only visible to logged in frontend users.

3) Add the Plugin "Change Frontend User Password" to the page created in step 2

4) Change TypoScript settings to your needs. Please note, that if you want to use the password change enforcement,
you **must** set `settings.changePasswordPid` to the page uid of the page created in step 2

5) Optionally change the path to the extension templates in TypoScript and modify the templates to your needs.

## New fe_user fields

The extension adds two new fields to the fe_users table (see screenshot)

![Screenshot of a fe_users](Documentation/Images/fe-user-password-settings.png "New fields in fe_users table")

If the checkbox "User must change password at next login" is set and a valid `changePasswordPid` is configured,
the user will be redirected to the configured page after login when accessing pages as configured in
the `plugin.tx_fechangepwd.settings.redirect` section.

The password expiry date defines the date, after a user must change the password.

**Tip:** If you quickly want all frontend users to change their passwords, you can use a simple SQL statement
to set the field in the database like shown in this example `UPDATE fe_users set must_change_password=1;`

## TypoScript configuration settings

The following TypoScript settings are available.

**plugin.tx_fechangepwd.settings**

* `changePasswordPid` *(integer)* The pid to redirect to if a password change is required. This is usually the
page with the Plugin of the extension

**plugin.tx_fechangepwd.settings.requireCurrentPassword**

* `enabled` *(bool)* If set to `1`, the user must enter the current password in order to set a new password. Default setting is `1`.

**plugin.tx_fechangepwd.settings.passwordExpiration**

* `enabled` *(bool)* Is set to `1`, new passwords will expire after the configured amount of days
* `validityInDays` *(integer)* The amount of days, a new password is valid before it needs to be changed

**plugin.tx_fechangepwd.settings.redirect**

* `allAccessProtectedPages` *(bool)* If set to `1`, a redirect to the configured `changePasswordPid` will be forced
for all access protected pages. Note, that if this option is set, the `includePageUids` is ignored!
* `includePageUids` *(string)* A redirect to the configured changePasswordPid will be forced for the configured PIDs separated by a comma
* `includePageUidsRecursionLevel` *(integer)* The recursion level for all pages configured in `includePageUids`. Use this option,
if you e.g. want to force a redirect for a page and all subpages
* `excludePageUids` (string) No redirect will be forced for the configured PIDs separated by a comma
* `excludePageUidsRecursionLevel` *(integer)* The recursion level for all pages configured in `excludePageUids`. Use this option,
if you e.g. want to exclude a page and all subpages for the redirect

**plugin.tx_fechangepwd.settings.afterPasswordChangeAction**

* `redirect` *(string)* Redirects the user to the "update" action and adds a flash message, that the password has been updated.
* `view` *(string)* Shows the view for the update action with a message, that the password has been updated

## Styling

The extension output is completely unstyled. Feel free to [override](https://stackoverflow.com/questions/39724833/best-way-to-overwrite-a-extension-template)
the fluid templates to your needs.

## Possible Errors

### No password hashing service

The extension will not save a users password, if it can not be hashed. If this scenario occurs,
the following exception is shown:

`No secure password hashing service could be initialized. Please check your TYPO3 system configuration`

### Possible CSRF detected

When the extension detects a possible CSRF, the following message is shown:

`Possible CSRF detected. Ensure a valid "changeHmac" is provided.`

If you unexpectedly see this message, ensure you add the `changeHmac` property as described in "Breaking Changes"
for version 1.5.0

## For developers

### PSR-14 events

The extension currently contains the following PSR-14 event:

* Derhansen\FeChangePwd\Controller\PasswordController
  * `AfterPasswordUpdatedEvent`

Additionally, the extension also dispatches the TYPO3 core PSR-14 event
`TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent`

If additional user data has to be considered for password validation, please
use this event to add the data to the `ContextData` DTO.

## Versions

| Version | TYPO3      | PHP       | Support/Development                  |
|---------|------------|-----------|--------------------------------------|
| 4.x     | 12.4       | 8.1 -8.2  | Features, Bugfixes, Security Updates |
| 3.x     | 11.5       | 7.4 -8.1  | Features, Bugfixes, Security Updates |
| 2.x     | 9.5 - 10.4 | 7.2 - 7.4 | Security Updates                     |
| 1.x     | 8.7 - 9.5  | 7.0 - 7.3 | Support dropped                      |

## Breaking changes

###  Version 4.0.0

This version contains major breaking changes, since now the TYPO3 password
policy is used for password validation.

* All password validators have been removed in favor to TYPO3 password policies. Make sure to check, if the TYPO3 default password policy suits your needs
* The pwned password check has been removed. If this check is required, please use TYPO3 extension [add_pwd_policy](https://github.com/derhansen/add_pwd_policy) in the password policy for frontend users
* The extension now requires the current user password by default. This check can be disabled in settings using `requireCurrentPassword`
* The extension requires TYPO3 `security.usePasswordPolicyForFrontendUsers` feature toggle to be active
* Dropped TYPO3 11.5 compatibility.

###  Version 3.0.0

* Dropped TYPO3 9.5 and 10.4 compatibility.
* Changed file extension für TypoScript files to `.typoscript`
* Replaced signal slot with PSR-14 event

###  Version 2.0.0

Dropped TYPO3 8.7 compatibility.

###  Version 1.5.0

**Added CSRF protection.**

If you use an own template for "Edit.html", you must add the following code inside `<f:form>...</f:form>`.

```
<f:form.hidden property="changeHmac" />
```

Prior to version 1.5.0, the extension did contain a CSRF vulnerability, if `settings.requireCurrentPassword` was
disabled (default). In order to mitigate the issue, the property `changeHmac` has been added to the DTO. This
property contains a HMAC, which is unique for the current logged-in user. When the provided `changeHmac` does not
match the expected value, an exception is thrown when the form is submitted.

## Thanks for sponsoring

* Thanks to [Wikafi sprl](https://www.wikafi.be) for sponsoring the initial development of this
extension and for supporting open source software.

* Thanks to [t3site.com](https://www.t3site.com/) for sponsoring the "Require current password" feature.
