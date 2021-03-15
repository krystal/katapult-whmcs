<?php

namespace WHMCS\Module\Server\Katapult\Helpers;

use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

class GeneralHelper
{
	public static function attempt(callable $task, string $taskName): void
	{
		try {
			$task();
			KatapultWhmcs::log("Successfully ran task: {$taskName}");
		} catch (\Throwable $e) {
			KatapultWhmcs::log("Error running task: {$taskName}: {$e->getMessage()}");
		}
	}

}


