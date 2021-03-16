<?php

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\Helpers\GeneralHelper;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

class System
{
	public static function syncConfigOptions(): void
	{
		GeneralHelper::attempt([KatapultWhmcs::class, 'syncConfigurableOptions'], 'Sync config options');
	}

	public static function syncVmBuilds(): void
	{
		GeneralHelper::attempt([KatapultWhmcs::class, 'syncVmBuilds'], 'Syncing VM builds');
	}
}

