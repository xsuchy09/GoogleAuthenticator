<?php
/******************************************************************************
 * Author: Petr Suchy (xsuchy09) <suchy@wamos.cz> <https://www.wamos.cz>
 * Project: googleauthenticator
 * Date: 21.08.19
 * Time: 13:20
 * Copyright: (c) Petr Suchy (xsuchy09) <suchy@wamos.cz> <http://www.wamos.cz>
 *****************************************************************************/

declare(strict_types=1);

namespace GoogleAuthenticator;

use Exception;
use GoogleAuthenticator\Exception\GoogleAuthenticatorException;

/**
 * Class GoogleAuthenticator
 *
 * PHP Class for handling Google Authenticator 2-factor authentication.
 *
 * @package TwoFactorAuthentication
 *
 * @author    Michael Kliewe
 * @copyright 2012 Michael Kliewe
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 *
 * @link      http://www.phpgangsta.de/
 *
 * @author    Petr Suchy - changes - namespace, PHP 7.1+ type hints etc
 * @copyright Ing. Petr Suchy, WAMOS.cz
 * @link      https://www.wamos.cz
 */
class GoogleAuthenticator
{

	/**
	 * Google Charts QR level options.
	 * L - [Default] Allows recovery of up to 7% data loss
	 * M - Allows recovery of up to 15% data loss
	 * Q - Allows recovery of up to 25% data loss
	 * H - Allows recovery of up to 30% data loss
	 */
	const QR_CODE_ERROR_CORRECTION_LEVELS = ['L', 'M', 'Q', 'H'];

	/**
	 * QR codes default params.
	 */
	const QR_CODE_DEFAULT_PARAMS = [
		'width' => 200,
		'height' => 200,
		'level' => 'M'
	];

	/**
	 * @var int
	 */
	protected $_codeLength = 6;

	/**
	 * Create new secret.
	 * 16 characters, randomly chosen from the allowed base32 characters.
	 *
	 * @param int $secretLength
	 *
	 * @return string
	 * @throws GoogleAuthenticatorException
	 */
	public function createSecret(int $secretLength = 16): string
	{
		$validChars = $this->_getBase32LookupTable();

		// Valid secret lengths are 80 to 640 bits
		if ($secretLength < 16 || $secretLength > 128) {
			throw new GoogleAuthenticatorException('Wrong secret length', GoogleAuthenticatorException::WRONG_SECRET_LENGTH);
		}
		$secret = '';
		$rnd = false;
		// @codeCoverageIgnoreStart
		if (true === function_exists('random_bytes')) {
			try {
				$rnd = random_bytes($secretLength);
			} catch (Exception $e) {
				$rnd = false;
			}
		}
		if ($rnd === false) {
			if (true === function_exists('openssl_random_pseudo_bytes')) {
				$rnd = openssl_random_pseudo_bytes($secretLength, $cryptoStrong);
				if (false === $cryptoStrong) {
					$rnd = false;
				}
			}
		}
		if ($rnd === false) {
			throw new GoogleAuthenticatorException('No source of secure random', GoogleAuthenticatorException::NO_SECURE_RANDOM_SOURCE);
		}
		// @codeCoverageIgnoreEnd

		for ($i = 0; $i < $secretLength; ++$i) {
			$secret .= $validChars[ord($rnd[$i]) & 31];
		}

		return $secret;
	}

	/**
	 * Calculate the code, with given secret and point in time.
	 *
	 * @param string   $secret
	 * @param int|null $timeSlice
	 *
	 * @return string
	 * @throws GoogleAuthenticatorException
	 */
	public function getCode(string $secret, ?int $timeSlice = null): string
	{
		if ($timeSlice === null) {
			$timeSlice = (int)floor(time() / 30);
		}

		$secretKey = $this->_base32Decode($secret);

		// Pack time into binary string
		$time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
		// Hash it with users secret key
		$hm = hash_hmac('SHA1', $time, $secretKey, true);
		// Use last nipple of result as index/offset
		$offset = ord(substr($hm, -1)) & 0x0F;
		// grab 4 bytes of the result
		$hashPart = substr($hm, $offset, 4);

		// Unpak binary value
		$value = unpack('N', $hashPart);
		$value = $value[1];
		// Only 32 bits
		$value = $value & 0x7FFFFFFF;

		$modulo = pow(10, $this->_codeLength);

		return str_pad((string)($value % $modulo), $this->_codeLength, '0', STR_PAD_LEFT);
	}

	/**
	 * Get QR-Code URL for image, from google charts.
	 *
	 * @param string      $name
	 * @param string      $secret
	 * @param string|null $title
	 * @param array|null  $params
	 *
	 * @return string
	 *
	 * @deprecated
	 * @deprecated 2.0.1
	 * @deprecated Don't use external servers to generate QR codes with your secret information. Use library (with specific version or fork to don't have security risk) like https://github.com/chillerlan/php-qrcode or https://github.com/endroid/qr-code etc.
	 * @deprecated Google set its chart api for qr codes as deprecated at all.
	 */
	public function getQRCodeGoogleUrl(string $name, string $secret, ?string $title = null, ?array $params = null): string
	{
		$qrParams = $this->getQRParams($params);
		$otpAuthLink = $this->getOtpAuthLink($name, $secret, $title);
		return sprintf('https://chart.apis.google.com/chart?cht=qr&chs=%dx%d&chl=%s&chld=%s|0', $qrParams['width'], $qrParams['height'], urlencode($otpAuthLink), $qrParams['level']);
	}

	/**
	 * Get QR Code from qrserver.com.
	 *
	 * @param string      $name
	 * @param string      $secret
	 * @param string|null $title
	 * @param array|null  $params
	 *
	 * @return string
	 *
	 * @deprecated
	 * @deprecated 2.0.1
	 * @deprecated Don't use external servers to generate QR codes with your secret information. Use library (with specific version or fork to don't have security risk) like https://github.com/chillerlan/php-qrcode or https://github.com/endroid/qr-code etc.
	 */
	public function getQRCodeQRServerUrl(string $name, string $secret, ?string $title = null, ?array $params = null): string
	{
		$qrParams = $this->getQRParams($params);
		$otpAuthLink = $this->getOtpAuthLink($name, $secret, $title);
		return sprintf('https://api.qrserver.com/v1/create-qr-code/?data=%s&size=%dx%d&ecc=%s', urlencode($otpAuthLink), $qrParams['width'], $qrParams['height'], $qrParams['level']);
	}

	/**
	 * Get params for QR code.
	 *
	 * @param array|null $params
	 *
	 * @return array
	 */
	protected function getQRParams(?array $params = null): array
	{
		$qrParams = self::QR_CODE_DEFAULT_PARAMS;
		if ($params !== null) {
			if (true === isset($params['width']) && (int)$params['width'] > 0) {
				$qrParams['width'] = (int)$params['width'];
			}
			if (true === isset($params['height']) && (int)$params['height'] > 0) {
				$qrParams['height'] = (int)$params['height'];
			}
			if (true === isset($params['level']) && array_search($params['level'], self::QR_CODE_ERROR_CORRECTION_LEVELS) !== false) {
				$qrParams['level'] = $params['level'];
			}
		}
		return $qrParams;
	}

	/**
	 * Get OTP Auth Link.
	 *
	 * @param string      $name
	 * @param string      $secret
	 * @param string|null $title
	 *
	 * @return string
	 */
	protected function getOtpAuthLink(string $name, string $secret, ?string $title = null): string
	{
		$otpAuthLink = sprintf('otpauth://totp/%s?secret=%s', urlencode($name), urlencode($secret));
		if ($title !== null && strlen($title) > 0) {
			$otpAuthLink .= sprintf('&issuer=%s', urlencode($title));
		}
		return $otpAuthLink;
	}

	/**
	 * Check if the code is correct. This will accept codes starting from $discrepancy*30sec ago to $discrepancy*30sec from now.
	 *
	 * @param string   $secret
	 * @param string   $code
	 * @param int      $discrepancy
	 * @param int|null $currentTimeSlice
	 *
	 * @return bool
	 * @throws GoogleAuthenticatorException
	 */
	public function verifyCode(string $secret, string $code, int $discrepancy = 1, ?int $currentTimeSlice = null): bool
	{
		if ($currentTimeSlice === null) {
			$currentTimeSlice = (int)floor(time() / 30);
		}

		if (strlen($code) !== $this->_codeLength) {
			throw new GoogleAuthenticatorException('Code has no valid length.', GoogleAuthenticatorException::CODE_NOT_VALID_LENGTH);
		}

		for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
			$calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
			if (true === $this->timingSafeEquals($calculatedCode, $code)) {
				return true;
			}
		}

		throw new GoogleAuthenticatorException('Code is not valid.', GoogleAuthenticatorException::CODE_NOT_VALID);
	}

	/**
	 * Set the code length, should be >=6.
	 *
	 * @param int $length
	 *
	 * @return GoogleAuthenticator
	 */
	public function setCodeLength(int $length): GoogleAuthenticator
	{
		$this->_codeLength = $length;

		return $this;
	}

	/**
	 * Helper class to decode base32.
	 *
	 * @param string $secret
	 *
	 * @return string|bool
	 * @throws GoogleAuthenticatorException
	 */
	protected function _base32Decode(string $secret)
	{
		if (true === empty($secret)) {
			return '';
		}

		$base32chars = $this->_getBase32LookupTable();
		$base32charsFlipped = array_flip($base32chars);

		$paddingCharCount = substr_count($secret, $base32chars[32]);
		$allowedValues = [6, 4, 3, 1, 0];
		if (false === in_array($paddingCharCount, $allowedValues)) {
			throw new GoogleAuthenticatorException('Base32 decode - value is not valid.', GoogleAuthenticatorException::BASE32_VALUE_NOT_VALID_PADDING); // @codeCoverageIgnore
		}
		for ($i = 0; $i < 4; ++$i) {
			if ($paddingCharCount === $allowedValues[$i] &&
				substr($secret, -($allowedValues[$i])) !== str_repeat($base32chars[32], $allowedValues[$i])) {
				throw new GoogleAuthenticatorException('Base32 decode - value is not valid.', GoogleAuthenticatorException::BASE32_VALUE_NOT_VALID); // @codeCoverageIgnore
			}
		}
		$secret = str_replace('=', '', $secret);
		$secret = str_split($secret);
		$binaryString = '';
		for ($i = 0; $i < count($secret); $i = $i + 8) {
			$x = '';
			if (false === in_array($secret[$i], $base32chars)) {
				throw new GoogleAuthenticatorException('Base32 decode - value is not valid.', GoogleAuthenticatorException::BASE32_VALUE_NOT_VALID_CHAR); // @codeCoverageIgnore
			}
			for ($j = 0; $j < 8; ++$j) {
				$x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
			}
			$eightBits = str_split($x, 8);
			for ($z = 0; $z < count($eightBits); ++$z) {
				$binaryString .= (($y = chr((int)base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
			}
		}

		return $binaryString;
	}

	/**
	 * Get array with all 32 characters for decoding from/encoding to base32.
	 *
	 * @return array
	 */
	protected function _getBase32LookupTable(): array
	{
		return [
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
			'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
			'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
			'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
			'=',  // padding char
		];
	}

	/**
	 * A timing safe equals comparison
	 * more info here: http://blog.ircmaxell.com/2014/11/its-all-about-time.html.
	 *
	 * @param string $safeString The internal (safe) value to be checked
	 * @param string $userString The user submitted (unsafe) value
	 *
	 * @return bool True if the two strings are identical
	 */
	private function timingSafeEquals(string $safeString, string $userString): bool
	{
		if (function_exists('hash_equals')) {
			return hash_equals($safeString, $userString);
		}
		// @codeCoverageIgnoreStart
		$safeLen = strlen($safeString);
		$userLen = strlen($userString);

		if ($userLen !== $safeLen) {
			return false;
		}

		$result = 0;

		for ($i = 0; $i < $userLen; ++$i) {
			$result |= (ord($safeString[$i]) ^ ord($userString[$i]));
		}

		// They are only identical strings if $result is exactly 0...
		return ($result === 0);
		// @codeCoverageIgnoreEnd
	}
}
