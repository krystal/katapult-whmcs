<?php

use Krystal\Katapult\Katapult;
use WHMCS\Module\Server\Katapult\Adaptation\AdminArea as AdminAreaAdaptation;
use WHMCS\Module\Server\Katapult\Adaptation\System as SystemAdaptation;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

require_once 'vendor/autoload.php';

function katapult(): Katapult
{
	return KatapultWhmcs::getKatapult();
}

// System
\add_hook('DailyCronJob', 0, [SystemAdaptation::class, 'syncConfigOptions']);

// Admin
\add_hook('AdminProductConfigFields', 0, [AdminAreaAdaptation::class, 'addConfigurationPaneToProductSettings']);
\add_hook('AdminProductConfigFieldsSave', 0, [AdminAreaAdaptation::class, 'updateKatapultConfiguration']);


