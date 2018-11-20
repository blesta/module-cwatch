# cWatch Module

[![Build Status](https://travis-ci.org/blesta/module-cwatch.svg?branch=master)](https://travis-ci.org/blesta/module-cwatch) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-cwatch/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-cwatch?branch=master)

This is a module for Blesta that integrates with [cWatch](https://cwatch.comodo.com/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/cwatch
    ```

2. OR upload the source code to a /components/modules/cwatch/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/cwatch/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the cWatch module, click the "Install", and enter your cWatch username and password

## When creating a package
#### For multi-license packages

1. Add quantity configurable options for each license type you wish to offer.  Available products include:

    ```
    BASIC_DETECTION
    STARTER
    PRO
    PRO_FREE
    PRO_FREE_60D
    PREMIUM
    PREMIUM_FREE
    PREMIUM_FREE_60D
    ```

2. The 'Name' of your configurable option must match the product exactly as shown here (case-sensitive).

3. The licenses are only available for the following payment terms, set config option payment terms accordingly:

    ```
    BASIC_DETECTION - any
    STARTER - 1 Month, 12 Months, 24 Month, 36 Months
    PRO - 1 Month, 12 Months, 24 Month, 36 Months
    PRO_FREE - 1 Month
    PRO_FREE_60D - 2 Months
    PREMIUM - 1 Month, 12 Months, 24 Month, 36 Months
    PREMIUM_FREE - 1 Month
    PREMIUM_FREE_60D - 2 Months
    ```

#### For single-license packages

1. The licenses are only available for the following payment terms, set package payment terms accordingly:

    ```
    BASIC_DETECTION - any
    STARTER - 1 Month, 12 Months, 24 Month, 36 Months
    PRO - 1 Month, 12 Months, 24 Month, 36 Months
    PRO_FREE - 1 Month
    PRO_FREE_60D - 2 Months
    PREMIUM - 1 Month, 12 Months, 24 Month, 36 Months
    PREMIUM_FREE - 1 Month
    PREMIUM_FREE_60D - 2 Months
    ```