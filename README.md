[![Build Status](https://travis-ci.org/derhansen/fe_change_pwd.svg?branch=master)](https://travis-ci.org/derhansen/fe_change_pwd)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/derhansen/fe_change_pwd/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/derhansen/fe_change_pwd/?branch=master)

# Change password for frontend users

## What does it do?

This TYPO3 extension contains a plugin to allow logged in frontend users to change their password. Additionally
the extension allows to define password rules for frontend user passwords and can also check if the password
was part of a data breach using the [haveibeenpwned.com](https://haveibeenpwned.com/) API.

Password changes for frontend users can be enforced and passwords can expire after a certain amount of days.

**Features:**

* Change password plugin
* Configurable password rules (upper case char, lower case char, digit, special char)
* Force password change for frontend users
* Redirect to configured page when password change is required (uses PSR-15 Middleware in TYPO3 v9)
* Password expiration after a configurable amount of days
* Optional check if password has been part of a data breach using the [haveibeenpwned.com](https://haveibeenpwned.com/) API and the k-Anonymity model

## Screenshot

The screenshot below shows the output of the "Change Frontend User Password" plugin after the user tried to submit  
a weak password.

![Screenshot of the plugin outpu](Documentation/Images/plugin-output.png "Output of the plugin after password validation")

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
the user will be redirected to the configured page after login or whe accessing pages configured in the 
`plugin.tx_fechangepwd.settings.redirect` section.

The password expiry date defines the date, after a user must change the password. 

**Tip:** If you quickly want all frontend users to change their passwords, you can use a simple SQL statement
to set the field in the database like shown in this example `UPDATE fe_users set must_change_password=1;`

## TypoScript configuration settings

The following TypoScript settings are available. 

**plugin.tx_fechangepwd.settings**

* `changePasswordPid` *(integer)* The pid to redirect to if a password change is required. This is usually the 
page with the Plugin of the extension

**plugin.tx_fechangepwd.settings.passwordComplexity**

* `minLength` *(integer)* Minimum length for the password.
* `capitalCharCheck` *(bool)* Is set to `1`, the password must at least contain one capital character.
* `lowerCaseCharCheck` *(bool)* Is set to `1`, the password must at least contain one lower case character
* `digitCheck` *(bool)* Is set to `1`, the password must at least contain one digit
* `specialCharCheck` *(bool)* Is set to `1`, the password must at least contain one special character

**plugin.tx_fechangepwd.settings.pwnedpasswordsCheck**

* `enabled` *(bool)* If set to `1`, the new password is checked using the haveibeenpwned.com API to verify, that the 
password has not been exposed in a data breach. Note, that the API uses a k-Anonymity model, so no passwords are 
submitted to the external service. Read more about it [here](https://haveibeenpwned.com/API/v2#SearchingPwnedPasswordsByRange)

**plugin.tx_fechangepwd.settings.oldPasswordCheck**

* `enabled` *(bool)* If set to `1`, it is checked, if the new password equals the old password and if so, the user
must choose a different password.

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

## Styling

The extension output is completely unstyled. Feel free to [override](https://stackoverflow.com/questions/39724833/best-way-to-overwrite-a-extension-template) 
the fluid templates to your needs.

## Possible Errors

The extension will not save a users password, if it can not be securily be hashed. If this scenario occurs,
the following exception is shown: 

`No secure password hashing service could be initialized. Please check your TYPO3 system configuration`

In TYPO3 8.7, you must ensure, that the Setting `basic.FE.enabled` is enabled for the `saltedpasswords` extension.  

## Thanks for sponsoring

I would like to thank [Wikafi sprl](https://www.wikafi.be) for sponsoring the initial development of this 
extension and for supporting open source software.

## Additional thanks

Also a big thanks to Troy Hunt for his remarkable work and the [haveibeenpwned.com](https://haveibeenpwned.com/) service and API. 