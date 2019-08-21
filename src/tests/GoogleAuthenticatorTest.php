<?php
/******************************************************************************
 * Author: Petr Suchy (xsuchy09) <suchy@wamos.cz> <https://www.wamos.cz>
 * Project: googleauthenticator
 * Date: 21.08.19
 * Time: 13:20
 * Copyright: (c) Petr Suchy (xsuchy09) <suchy@wamos.cz> <http://www.wamos.cz>
 *****************************************************************************/

declare(strict_types=1);

namespace GoogleAuthenticator\Tests;

use GoogleAuthenticator\Exception\GoogleAuthenticatorException;
use GoogleAuthenticator\GoogleAuthenticator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

final class GoogleAuthenticatorTest extends TestCase
{
	/* @var $googleAuthenticator GoogleAuthenticator */
	protected static $googleAuthenticator;

	public static function setUpBeforeClass(): void
	{
		self::$googleAuthenticator = new GoogleAuthenticator();
	}

	public function codeProvider(): array
	{
		// Secret, time, code
		return [
			['SECRET', 0, '200470'],
			['SECRET', 1385909245, '780018'],
			['SECRET', 1378934578, '705013'],
			['', 0, '328482']
		];
	}

	public function testItCanBeInstantiated(): void
	{
		$ga = new GoogleAuthenticator();

		$this->assertInstanceOf(GoogleAuthenticator::class, $ga);
	}

	public function testCreateSecretDefaultsToSixteenCharacters(): void
	{
		$ga = self::$googleAuthenticator;
		$secret = $ga->createSecret();

		$this->assertEquals(strlen($secret), 16);
	}

	public function testCreateSecretLengthCanBeSpecified(): void
	{
		$ga = self::$googleAuthenticator;

		for ($secretLength = 16; $secretLength < 100; ++$secretLength) {
			$secret = $ga->createSecret($secretLength);

			$this->assertEquals(strlen($secret), $secretLength);
		}
	}

	public function testCreateSecretExceptionLength(): void
	{
		$this->expectException(GoogleAuthenticatorException::class);
		$this->expectExceptionCode(GoogleAuthenticatorException::WRONG_SECRET_LENGTH);

		$ga = self::$googleAuthenticator;
		$ga->createSecret(5);
	}

	/**
	 * @dataProvider codeProvider
	 */
	public function testGetCodeReturnsCorrectValues(string $secret, int $timeSlice, string $code): void
	{
		$generatedCode = self::$googleAuthenticator->getCode($secret, $timeSlice);

		$this->assertEquals($code, $generatedCode);
	}

	public function testGetQRCodeGoogleUrlReturnsCorrectUrl(): void
	{
		$secret = 'SECRET';
		$name = 'Test';
		$url = self::$googleAuthenticator->getQRCodeGoogleUrl($name, $secret);
		$urlParts = parse_url($url);

		parse_str($urlParts['query'], $queryStringArray);

		$this->assertEquals($urlParts['scheme'], 'https');
		$this->assertEquals($urlParts['host'], 'api.qrserver.com');
		$this->assertEquals($urlParts['path'], '/v1/create-qr-code/');

		$expectedChl = 'otpauth://totp/' . $name . '?secret=' . $secret;

		$this->assertEquals($queryStringArray['data'], $expectedChl);
	}

	public function testGetQRCodeGoogleUrlReturnsCorrectUrlWithParams(): void
	{
		// with params
		$secret = 'SECRET';
		$name = 'Test';
		$title = 'Title';
		$params = [
			'width' => 300,
			'height' => 300,
			'level' => 'L'
		];
		$url = self::$googleAuthenticator->getQRCodeGoogleUrl($name, $secret, $title, $params);
		$urlParts = parse_url($url);

		parse_str($urlParts['query'], $queryStringArray);

		$this->assertEquals($urlParts['scheme'], 'https');
		$this->assertEquals($urlParts['host'], 'api.qrserver.com');
		$this->assertEquals($urlParts['path'], '/v1/create-qr-code/');

		$expectedChl = sprintf('otpauth://totp/%s?secret=%s&issuer=%s', $name, $secret, $title);

		$this->assertEquals($expectedChl, $queryStringArray['data']);
	}

	public function testVerifyCode(): void
	{
		$secret = 'SECRET';
		$code = self::$googleAuthenticator->getCode($secret);
		$result = self::$googleAuthenticator->verifyCode($secret, $code);

		$this->assertEquals(true, $result);
	}

	public function testVerifyCodeException(): void
	{
		$secret = 'SECRET';
		$this->expectException(GoogleAuthenticatorException::class);
		$this->expectExceptionCode(GoogleAuthenticatorException::CODE_NOT_VALID_LENGTH);

		$code = 'INVALIDCODE';
		$result = self::$googleAuthenticator->verifyCode($secret, $code);
	}

	public function testVerifyCodeException2(): void
	{
		$secret = 'SECRET';
		$this->expectException(GoogleAuthenticatorException::class);
		$this->expectExceptionCode(GoogleAuthenticatorException::CODE_NOT_VALID);

		$code = '123456';
		$result = self::$googleAuthenticator->verifyCode($secret, $code);
	}

	public function testVerifyCodeWithLeadingZero(): void
	{
		$secret = 'SECRET';
		$code = self::$googleAuthenticator->getCode($secret);
		$result = self::$googleAuthenticator->verifyCode($secret, $code);
		$this->assertEquals(true, $result);


		$this->expectException(GoogleAuthenticatorException::class);
		$this->expectExceptionCode(GoogleAuthenticatorException::CODE_NOT_VALID_LENGTH);

		$code = '0' . $code;
		$result = self::$googleAuthenticator->verifyCode($secret, $code);
	}

	public function testSetCodeLength(): void
	{
		$result = self::$googleAuthenticator->setCodeLength(6);

		$this->assertInstanceOf(GoogleAuthenticator::class, $result);
	}
}
