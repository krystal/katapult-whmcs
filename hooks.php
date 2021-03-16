<?php

use Krystal\Katapult\Katapult;
use WHMCS\Module\Server\Katapult\Adaptation\AdminArea as AdminAreaAdaptation;
use WHMCS\Module\Server\Katapult\Adaptation\System as SystemAdaptation;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/helpers.php');

// System
\add_hook('DailyCronJob', 0, [SystemAdaptation::class, 'syncConfigOptions']);
\add_hook('AfterCronJob', 0, [SystemAdaptation::class, 'syncVmBuilds']);

// Admin
\add_hook('AdminProductConfigFields', 0, [AdminAreaAdaptation::class, 'addConfigurationPaneToProductSettings']);
\add_hook('AdminProductConfigFieldsSave', 0, [AdminAreaAdaptation::class, 'updateKatapultConfiguration']);


