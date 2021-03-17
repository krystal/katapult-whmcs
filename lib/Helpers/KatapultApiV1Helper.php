<?php

namespace WHMCS\Module\Server\Katapult\Helpers;

use GuzzleHttp\Exception\ClientException;

class KatapultApiV1Helper
{
	/**
	 * @param ClientException $exception
	 * @return array|string[]
	 *
	 * An array is returned for future use, should multiple errors be returned.
	 */
	public static function humaniseHttpError(ClientException $exception): array
	{
		$responseBody = \GuzzleHttp\Utils::jsonDecode(
			$exception->getResponse()->getBody()
		);

		if (!$responseBody->error) {
			return [
				'Unknown error occurred'
			];
		}

		$primaryError = $responseBody->error->description;

		// Add the errors..
		if(isset($responseBody->error->detail) && isset($responseBody->error->detail->errors) && count($responseBody->error->detail->errors) > 0) {
			$primaryError .= " - " . implode(', ', $responseBody->error->detail->errors);
		}


		// Add the details..
		if(isset($responseBody->error->detail) && isset($responseBody->error->detail->details)) {
			$primaryError .= " - " . $responseBody->error->detail->details;
		}

		return [$primaryError];
	}
}

