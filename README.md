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

$name = 'suchy';
$secret = $ga->createSecret();
$title = 'WAMOS.cz';

echo sprintf('Name is: %s', $name) . PHP_EOL;
echo sprintf('Secret is: %s', $secret) . PHP_EOL;
echo sprintf('Title is: %s', $title) . PHP_EOL . PHP_EOL;

$dataToRender = $ga->getOtpAuthLink($name, $secret, $title); // or getDataToRender method - just alis
echo sprintf('Data to render: %s', $dataToRender) . PHP_EOL . PHP_EOL;

// don't use this, don't share you security with third parties
$qrCodeUrl = $ga->getQRCodeGoogleUrl($name, $secret, $title);
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
Name is: suchy
Secret is: SECRET
Title is: WAMOS.cz

Data to render: otpauth://totp/suchy?secret=SECRET&issuer=WAMOS.cz

Google Charts URL for the QR-Code: https://chart.apis.google.com/chart?cht=qr&chs=200x200&chl=otpauth%3A%2F%2Ftotp%2Fsuchy%3Fsecret%3DSECRET%26issuer%3DWAMOS.cz&chld=M|0

Checking Code '123456' and Secret 'SECRET':
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


Security recommendation:
-----
Don't use methods `GoogleAuthenticator::getQRCodeGoogleUrl` and `GoogleAuthenticator::getQRCodeQRServerUrl`.
It is just for sample. Don't share your secret with third party. Use 
your own QR code generation. You can use libraries like:
- https://github.com/chillerlan/php-qrcode
- https://github.com/endroid/qr-code

But don't believe libraries of third parties too. Do security audit of 
third party library and make your own fork or don't update these 
libraries without checking the security of update. 


ToDo:
-----
- Nothing ... if you need something, [contact me](mailto:suchy@wamos.cz).

Notes:
------

If you like this script or have some features to add: contact me, visit my webpage, fork this project, send pull requests, you know how it works.
