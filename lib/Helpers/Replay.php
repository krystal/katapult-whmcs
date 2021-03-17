<?php

namespace WHMCS\Module\Server\Katapult\Helpers;

use Illuminate\Support\Str;

class Replay
{
	static bool $hasBooted = false;
	static ? string $previousToken = null;

	/**
	 * @return string
	 *
	 * Generates a new token
	 */
	protected static function generateToken(): string
	{
		return sha1(Str::random() . microtime());
	}

	/**
	 * @param string|null $token
	 * @return bool|null
	 *
	 * Checks the token is valid, but only for the client area. Else, null is returned.
	 */
	public static function tokenIsValidForClientArea(string $token = null): ? bool
	{
		if(!defined('CLIENTAREA')) {
			return null;
		}

		if(!CLIENTAREA) {
			return null;
		}

		return self::tokenIsValid($token);
	}

	/**
	 * @param string $token
	 * @return bool
	 *
	 * Checks whether a supplied token is valid
	 */
	public static function tokenIsValid(string $token = null): bool
	{
		self::init();

		if(!self::$previousToken) {
			return false;
		}

		if($token === null) {
			$token = trim($_REQUEST['knrp']) ?? null;
		}

		if(!$token) {
			return false;
		}

		return $token === self::$previousToken;
	}

	/**
	 * @return string
	 *
	 * Initialises the new and previous replay tokens, returns the new token
	 */
	public static function init(): string
	{
		if(self::$hasBooted) {
			return $_SESSION['knrp_token'];
		}

		self::$previousToken = $_SESSION['knrp_token'] ?? null;

		$token = self::generateToken();

		$_SESSION['knrp_token'] = $token;

		self::$hasBooted = true;

		return $token;
	}

	/**
	 * Fetches the current token
	 */
	public static function getToken(): string
	{
		return self::init();
	}
}




