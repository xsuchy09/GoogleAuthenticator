Google Authenticator PHP class
==============================

* Copyright (c) 2019, [https://www.wamos.cz](https://www.wamos.cz)
* Author: Petr Suchy, [suchy@wamos.cz](mailto:suchy@wamos.cz)
* Licensed under the BSD License.

[![Build Status](https://travis-ci.org/xsuchy09/GoogleAuthenticator.svg?branch=master)](https://travis-ci.org/xsuchy09/GoogleAuthenticator)

Fork from:
-----
[phpgangsta/googleauthenticator](https://github.com/PHPGangsta/GoogleAuthenticator)

Original copyright info:
* Copyright (c) 2012-2016, [http://www.phpgangsta.de](http://www.phpgangsta.de)
* Author: Michael Kliewe, [@PHPGangsta](http://twitter.com/PHPGangsta) and [contributors](https://github.com/PHPGangsta/GoogleAuthenticator/graphs/contributors)
* Licensed under the BSD License.

Description:
-----

This PHP class can be used to interact with the Google Authenticator mobile app for 2-factor-authentication. This class
can generate secrets, generate codes, validate codes and present a QR-Code for scanning the secret. It implements TOTP 
according to [RFC6238](https://tools.ietf.org/html/rfc6238)

For a secure installation you have to make sure that used codes cannot be reused (replay-attack). You also need to
limit the number of verifications, to fight against brute-force attacks. For example you could limit the amount of
verifications to 10 tries within 10 minutes for one IP address (or IPv6 block). It depends on your environment.

Usage:
------

See following example:

```php
<?php
require_once 'GoogleAuthenticator/GoogleAuthenticator.php';

$ga = new GoogleAuthenticator();
$secret = $ga->createSecret();
echo sprintf('Secret is: %s', $secret) . PHP_EOL . PHP_EOL;

$qrCodeUrl = $ga->getQRCodeGoogleUrl('Blog', $secret);
echo sprintf('Google Charts URL for the QR-Code: %s', $qrCodeUrl) . PHP_EOL . PHP_EOL;

$oneCode = $ga->getCode($secret);
echo sprintf('Checking Code %s and Secret %s:', $oneCode, $secret) . PHP_EOL;

$checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
if (true === $checkResult) {
    echo 'OK';
} else {
    echo 'FAILED';
}
```
Running the script provides the following output:
```
Secret is: OQB6ZZGYHCPSX4AK

Google Charts URL for the QR-Code: https://www.google.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/infoATphpgangsta.de%3Fsecret%3DOQB6ZZGYHCPSX4AK

Checking Code '848634' and Secret 'OQB6ZZGYHCPSX4AK':
OK
```

Installation:
-------------

- Use [Composer](https://getcomposer.org/doc/01-basic-usage.md) to
  install the package
  
```composer require xsuchy09/googleauthenticator```

- [Composer](https://getcomposer.org/doc/01-basic-usage.md) will take care of autoloading
  the library. Just include the following at the top of your file

  `require_once __DIR__ . '/../vendor/autoload.php';`

Run Tests:
----------

- All tests are inside `src/tests` folder.
- Execute `composer install` and then run the tests from project root directory.
- Shell script is prepared - just run `phpunit.sh` from the project root directory.
- It will generate code coverage report too inside `.phpunit` directory.


ToDo:
-----
- Nothing ... if you need something, [contact me](mailto:suchy@wamos.cz).

Notes:
------

If you like this script or have some features to add: contact me, visit my webpage, fork this project, send pull requests, you know how it works.
