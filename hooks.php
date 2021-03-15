<?php

use WHMCS\Module\Server\Katapult\Adaptation\AdminArea as AdminAreaAdaptation;

require_once 'vendor/autoload.php';

\add_hook('AdminProductConfigFields', 0, [AdminAreaAdaptation::class, 'addConfigurationPaneToProductSettings']);
\add_hook('AdminProductConfigFieldsSave', 0, [AdminAreaAdaptation::class, 'updateKatapultConfiguration']);




