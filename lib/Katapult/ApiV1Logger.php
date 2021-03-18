<?php

namespace WHMCS\Module\Server\Katapult\Katapult;

use Psr\Log\LoggerInterface;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

class ApiV1Logger implements LoggerInterface
{
	public function log($level, $message, array $context = [])
	{
		// Split the message up
		$action = explode('__KATAPULT_REQUEST__', $message, 2);

		// Request and response..
		$reqRes = explode('__KATAPULT_RESPONSE__', $action[1], 2);

		// Log it
		KatapultWhmcs::moduleLog(
			trim($action[0]),
			trim($reqRes[0]),
			trim($reqRes[1])
		);
	}

	public function emergency($message, array $context = [])
	{
		//
	}

	public function alert($message, array $context = [])
	{
		//
	}

	public function critical($message, array $context = [])
	{
		//
	}

	public function error($message, array $context = [])
	{
		//
	}

	public function warning($message, array $context = [])
	{
		//
	}

	public function notice($message, array $context = [])
	{
		//
	}

	public function info($message, array $context = [])
	{
		//
	}

	public function debug($message, array $context = [])
	{
		//
	}
}

