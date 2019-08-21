<?php
/******************************************************************************
 * Author: Petr Suchy (xsuchy09) <suchy@wamos.cz> <https://www.wamos.cz>
 * Project: googleauthenticator
 * Date: 21.08.19
 * Time: 13:22
 * Copyright: (c) Petr Suchy (xsuchy09) <suchy@wamos.cz> <http://www.wamos.cz>
 *****************************************************************************/

declare(strict_types=1);

namespace GoogleAuthenticator\Exception;


use Exception;

class GoogleAuthenticatorException extends Exception
{
	const WRONG_SECRET_LENGTH = 1;
	const NO_SECURE_RANDOM_SOURCE = 2;
	const CODE_NOT_VALID_LENGTH = 3;
	const CODE_NOT_VALID = 4;
	const BASE32_VALUE_NOT_VALID_PADDING = 5;
	const BASE32_VALUE_NOT_VALID = 6;
	const BASE32_VALUE_NOT_VALID_CHAR = 7;
}