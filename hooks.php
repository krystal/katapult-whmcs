<?php

use WHMCS\Module\Server\Katapult\Adaptation\AdminArea as AdminAreaAdaptation;
use WHMCS\Module\Server\Katapult\Adaptation\ClientArea as ClientAreaAdaptation;
use WHMCS\Module\Server\Katapult\Adaptation\System as SystemAdaptation;

if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}

require(__DIR__ . '/vendor/autoload.php');

// System
\add_hook('DailyCronJob', 0, [SystemAdaptation::class, 'syncConfigOptions']);
\add_hook('AfterCronJob', 0, [SystemAdaptation::class, 'syncVmBuilds']);

// Admin area
\add_hook('AdminProductConfigFields', 0, [AdminAreaAdaptation::class, 'addConfigurationPaneToProductSettings']);
\add_hook('AdminProductConfigFieldsSave', 0, [AdminAreaAdaptation::class, 'updateKatapultConfiguration']);
\add_hook('AdminAreaHeadOutput', 0, [AdminAreaAdaptation::class, 'addAssetsToHead']);

// Client area
\add_hook('ClientAreaHeadOutput', 0, [ClientAreaAdaptation::class, 'addAssetsToHead']);


