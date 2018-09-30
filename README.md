[![Build Status](https://travis-ci.org/derhansen/fe_change_pwd.svg?branch=master)](https://travis-ci.org/derhansen/fe_change_pwd)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/derhansen/fe_change_pwd/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/derhansen/fe_change_pwd/?branch=master)

# Change password for frontend users

## What does it do?

This TYPO3 extension contains a plugin to allow logged in frontend users to change their password. Additionally
the extension allows to define password rules for frontend user passwords and can also check if the password
was part of a data breach using the haveibeenpwned.com API.

Password changes for frontend users can be enforces and passwords can expire after a certain amount of days.

**Features:**

* Change password plugin
* Configurable password rules (upper case char, lower case char, digit, special char)
* Force password change for frontend users
* Redirect to configured page when password change is required
* Password expiration after a configurable amount of days
* Optional check if password has been part of a password breach using the haveibeenpwned.com API

## TypoScript configuration settings


## Styling

The extension output is completely unstyled. Feel free to [override](https://stackoverflow.com/questions/39724833/best-way-to-overwrite-a-extension-template) 
the fluid templates to your needs.

## Thanks for sponsoring

I would like to thank [Wikafi sprl](https://www.wikafi.be) for sponsoring the initial development of this 
extension and for supporting open source software. 