<?php

namespace WHMCS\Module\Server\Katapult;

use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;

class KatapultWhmcs
{
	const SERVER_MODULE = 'katapult';

	public static function getApiV1Key(): ? string
	{
		if (defined('KATAPULT_API_V1_KEY') && KATAPULT_API_V1_KEY) {
			return KATAPULT_API_V1_KEY;
		}

		$value = DataStore::get('module_setting', 'katapult', 'api_v1_key');

		if($value === DataStore::EMPTY_VALUE_INDEX) {
			return null;
		}

		return \decrypt($value);
	}

	public static function setApiV1Key(string $apiKey)
	{
		DataStore::set('module_setting', 'katapult', 'api_v1_key', \encrypt($apiKey));
	}
}

