<?php

namespace WHMCS\Module\Server\Katapult;

class KatapultHelper
{
	public static function getApiV1Key(): ? string
	{
		if (!defined('KATAPULT_API_V1_KEY')) {
			return null;
		}

		return KATAPULT_API_V1_KEY ?: null;
	}
}


